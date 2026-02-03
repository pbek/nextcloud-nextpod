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
