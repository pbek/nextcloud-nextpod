{
  pkgs,
  ...
}:

{
  # https://devenv.sh/packages/
  packages = with pkgs; [
    zellij
    libxml2 # for xmllint
  ];

  # https://devenv.sh/languages/
  languages.javascript = {
    # Too many Nextcloud libraries are still not compatible with Node.js 22
    package = pkgs.nodejs_20;
  };

  enterShell = ''
    echo "🛠️ Nextpod dev shell"
  '';

  # https://devenv.sh/git-hooks/
  git-hooks = {
    excludes = [
      "vendor/*"
      "appinfo/signature.json"
      "config/secrets/*"
      "config/bundles.php"
    ];
  };

  # See full reference at https://devenv.sh/reference/options/
}
