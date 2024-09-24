{ pkgs ? import <nixpkgs> {} }:
  pkgs.mkShell {
    nativeBuildInputs = with pkgs; [
      just
      zellij
      nodejs
      php83
      php83Packages.composer
      libxml2 # for xmllint
      docker-slim
    ];
}

