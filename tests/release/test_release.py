import base64
import hashlib
import importlib.util
import json
import tempfile
import unittest
from pathlib import Path
from unittest.mock import MagicMock, patch


def load(name):
    path = Path(__file__).resolve().parents[2] / "scripts" / f"{name}.py"
    spec = importlib.util.spec_from_file_location(name, path)
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


CHECK = load("check-release")
STORE = load("publish-appstore")
GITHUB = load("publish-github-release")
URL = "https://github.com/pbek/nextcloud-nextpod/releases/download/v0.7.12/nextpod-0.7.12.tar.gz"
COMMIT = "a" * 40
REPO = "pbek/nextcloud-nextpod"


class ReleaseCheckTest(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.root = Path(self.temp.name)
        entries = ["appinfo", "lib", "templates", "js", "img", "l10n"]
        for entry in entries:
            (self.root / entry).mkdir()
        for entry in ("LICENSE", "README.md", "CHANGELOG.md"):
            (self.root / entry).write_text("fixture")
            entries.append(entry)
        (self.root / "release-files.txt").write_text("\n".join(entries) + "\n")
        (self.root / "appinfo/info.xml").write_text(
            "<info><id>nextpod</id><version>0.7.12</version></info>"
        )
        (self.root / "js/nextpod-main.js").write_text("bundle")
        (self.root / "CHANGELOG.md").write_text("## 0.7.12\n\n- Release notes\n")
        for name in ("package.json", "package-lock.json"):
            (self.root / name).write_text(json.dumps({
                "version": "0.7.12", "packages": {"": {"version": "0.7.12"}}
            }))

    def test_valid_release(self):
        self.assertEqual(CHECK.check_release(self.root, True), ("0.7.12", "- Release notes\n"))

    def test_all_package_versions_must_match(self):
        for name in ("package.json", "package-lock.json"):
            path = self.root / name
            original = path.read_text()
            path.write_text(original.replace("0.7.12", "1.0.0"))
            with self.assertRaisesRegex(ValueError, "version differs"):
                CHECK.check_release(self.root)
            path.write_text(original)
        path = self.root / "package-lock.json"
        data = json.loads(path.read_text())
        data["packages"][""]["version"] = "1.0.0"
        path.write_text(json.dumps(data))
        with self.assertRaisesRegex(ValueError, "root package version differs"):
            CHECK.check_release(self.root)

    def test_invalid_versions(self):
        path = self.root / "appinfo/info.xml"
        original = path.read_text()
        for version in ("v0.7.12", "0.07.12", "0.7.12-rc1", "0.7"):
            path.write_text(original.replace("0.7.12", version))
            with self.assertRaisesRegex(ValueError, "major.minor.patch"):
                CHECK.check_release(self.root)

    def test_notes_must_be_unique_nonempty_and_newest(self):
        path = self.root / "CHANGELOG.md"
        for notes in ("## 0.7.12\n", "## 0.7.11\n- Old", "## 0.7.12\n- One\n## 0.7.12\n- Two", "## 0.7.13\n- Next\n## 0.7.12\n- Current"):
            path.write_text(notes)
            with self.assertRaisesRegex(ValueError, "CHANGELOG.md"):
                CHECK.check_release(self.root)

    def test_manifest_rejects_unsafe_duplicate_and_missing_paths(self):
        path = self.root / "release-files.txt"
        original = path.read_text()
        for entry in ("../outside", "/etc", "lib/*", "lib", "two words"):
            path.write_text(original + entry + "\n")
            with self.assertRaisesRegex(ValueError, "Invalid or duplicate"):
                CHECK.check_release(self.root)
        path.write_text(original + "missing\n")
        with self.assertRaisesRegex(ValueError, "Missing release input"):
            CHECK.check_release(self.root)
        path.write_text(original.replace("l10n\n", ""))
        with self.assertRaisesRegex(ValueError, "missing required inputs"):
            CHECK.check_release(self.root)

    def test_symlinks_are_rejected(self):
        (self.root / "lib/link").symlink_to(self.root / "LICENSE")
        with self.assertRaisesRegex(ValueError, "Symlinks"):
            CHECK.check_release(self.root)

    def test_frontend_check_is_optional(self):
        (self.root / "js/nextpod-main.js").unlink()
        CHECK.check_release(self.root)
        with self.assertRaisesRegex(ValueError, "frontend entry point"):
            CHECK.check_release(self.root, True)

    def test_development_can_have_newer_changelog_sections(self):
        path = self.root / "CHANGELOG.md"
        original = path.read_text()
        path.write_text("## 0.7.13\n- Next\n" + original)
        CHECK.check_release(self.root, allow_unreleased=True)
        for version in ("0.7.11", "Unreleased", "0.07.13"):
            path.write_text(f"## {version}\n- Invalid\n" + original)
            with self.assertRaisesRegex(ValueError, "newest section"):
                CHECK.check_release(self.root, allow_unreleased=True)

    def test_missing_screenshot_is_rejected(self):
        path = self.root / "appinfo/info.xml"
        path.write_text(path.read_text().replace("</info>", "<screenshot>https://raw.githubusercontent.com/pbek/nextcloud-nextpod/main/img/missing.png</screenshot></info>"))
        with self.assertRaisesRegex(ValueError, "screenshot"):
            CHECK.check_release(self.root)


class PublishTest(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.root = Path(self.temp.name)
        (self.root / "build").mkdir()
        self.archive = self.root / "build/nextpod-0.7.12.tar.gz"
        self.archive.write_bytes(b"signed archive")
        self.signature = self.archive.with_name(self.archive.name + ".sig")
        self.signature.write_text(base64.b64encode(b"signature").decode())
        self.checksum = self.archive.with_name(self.archive.name + ".sha256")
        self.checksum.write_text(f"{hashlib.sha256(self.archive.read_bytes()).hexdigest()}  {self.archive.name}\n")

    def response(self, content=b"signed archive", status=200):
        response = MagicMock()
        response.__enter__.return_value = response
        response.geturl.return_value = URL
        response.read.return_value = content
        response.status = status
        return response

    def test_store_sends_existing_signature_and_token_only_to_store(self):
        with patch.object(STORE.urllib.request, "urlopen", side_effect=[self.response(), self.response(status=201)]) as request:
            self.assertEqual(STORE.publish(URL, self.signature, self.archive, "token"), 201)
            self.assertEqual(request.call_args_list[0].args[0], URL)
            post = request.call_args_list[1].args[0]
            self.assertEqual(post.full_url, STORE.ENDPOINT)
            self.assertEqual(post.get_header("Authorization"), "Token token")
            self.assertEqual(json.loads(post.data), {"download": URL, "signature": self.signature.read_text(), "nightly": False})

    def test_store_refuses_changed_public_archive(self):
        with patch.object(STORE.urllib.request, "urlopen", return_value=self.response(b"different")) as request:
            with self.assertRaisesRegex(ValueError, "differs"):
                STORE.publish(URL, self.signature, self.archive, "token")
            self.assertEqual(request.call_count, 1)

    def test_store_rejects_bad_inputs_without_network(self):
        with patch.object(STORE.urllib.request, "urlopen") as request:
            for url, token in ((URL, ""), (URL, "token\n"), (URL.replace("v0.7.12", "v0.7.11"), "token"), (URL + "?token=x", "token")):
                with self.assertRaises(ValueError):
                    STORE.publish(url, self.signature, self.archive, token)
            self.signature.write_text("not base64!")
            with self.assertRaises(ValueError):
                STORE.publish(URL, self.signature, self.archive, "token")
            request.assert_not_called()

    @patch.object(GITHUB.subprocess, "check_output", return_value="0.7.12\n")
    def test_github_creates_draft_then_publishes(self, metadata):
        with patch.object(GITHUB, "gh", side_effect=[None, None, "", "", "", "", ""]) as gh:
            GITHUB.publish(self.root, COMMIT, REPO)
        calls = [call.args for call in gh.call_args_list]
        self.assertIn(COMMIT, calls[2])
        self.assertIn("--draft", calls[2])
        self.assertEqual(sum(call[:2] == ("release", "upload") for call in calls), 3)
        self.assertIn("--notes-file", calls[-1])
        self.assertIn("--draft=false", calls[-1])
        self.assertFalse(any("--clobber" in call for call in calls))

    @patch.object(GITHUB.subprocess, "check_output", return_value="0.7.12\n")
    def test_github_refuses_different_tag_commit(self, metadata):
        with patch.object(GITHUB, "gh", side_effect=[None, "{}", json.dumps({"sha": "b" * 40})]):
            with self.assertRaisesRegex(ValueError, "different commit"):
                GITHUB.publish(self.root, COMMIT, REPO)

    @patch.object(GITHUB.subprocess, "check_output", return_value="0.7.12\n")
    def test_github_refuses_tampered_checksum(self, metadata):
        self.archive.write_bytes(b"tampered")
        with patch.object(GITHUB, "gh") as gh:
            with self.assertRaisesRegex(ValueError, "checksum"):
                GITHUB.publish(self.root, COMMIT, REPO)
            gh.assert_not_called()

    @patch.object(GITHUB.subprocess, "check_output", return_value="0.7.12\n")
    def test_github_retry_preserves_identical_published_assets(self, metadata):
        assets = [self.archive, self.signature, self.checksum]

        def gh(*args, **kwargs):
            if args[0] == "api":
                if "releases/tags" in args[1]:
                    return json.dumps({"draft": False, "assets": [{"name": asset.name} for asset in assets]})
                return json.dumps({"sha": COMMIT})
            if args[:2] == ("release", "download"):
                name = args[args.index("--pattern") + 1]
                (Path(args[-1]) / name).write_bytes((self.archive.parent / name).read_bytes())
            return ""

        with patch.object(GITHUB, "gh", side_effect=gh) as mocked:
            GITHUB.publish(self.root, COMMIT, REPO)
        self.assertFalse(any(call.args[:2] in (("release", "upload"), ("release", "edit"), ("release", "create")) for call in mocked.call_args_list))

    @patch.object(GITHUB.subprocess, "check_output", return_value="0.7.12\n")
    def test_github_refuses_draft_for_another_commit(self, metadata):
        release = {"draft": True, "target_commitish": "b" * 40, "assets": []}
        with patch.object(GITHUB, "gh", side_effect=[json.dumps(release), None]):
            with self.assertRaisesRegex(ValueError, "different commit"):
                GITHUB.publish(self.root, COMMIT, REPO)

    @patch.object(GITHUB.subprocess, "check_output", return_value="0.7.12\n")
    def test_github_draft_resumes_only_identical_assets(self, metadata):
        for content in (self.archive.read_bytes(), b"different"):
            def gh(*args, **kwargs):
                if args[0] == "api":
                    if "releases/tags" in args[1]:
                        return json.dumps({"draft": True, "assets": [{"name": self.archive.name}]})
                    return json.dumps({"sha": COMMIT})
                if args[:2] == ("release", "download"):
                    (Path(args[-1]) / self.archive.name).write_bytes(content)
                return ""

            with patch.object(GITHUB, "gh", side_effect=gh) as mocked:
                if content == b"different":
                    with self.assertRaisesRegex(ValueError, "overwrite different"):
                        GITHUB.publish(self.root, COMMIT, REPO)
                    self.assertFalse(any(call.args[:2] == ("release", "upload") for call in mocked.call_args_list))
                else:
                    GITHUB.publish(self.root, COMMIT, REPO)
                    self.assertEqual(sum(call.args[:2] == ("release", "upload") for call in mocked.call_args_list), 2)


if __name__ == "__main__":
    unittest.main()
