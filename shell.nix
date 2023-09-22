{ pkgs ? import <nixpkgs> {} }:
  pkgs.mkShell {
    nativeBuildInputs = with pkgs; [
      gnumake
      nodejs
      php82
      php82Packages.composer
      libxml2 # for xmllint
    ];
}

