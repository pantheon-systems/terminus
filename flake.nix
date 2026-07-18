{
  description = "Description for the project";

  inputs = {
    flake-parts.url = "github:hercules-ci/flake-parts";
    nixpkgs.url = "github:NixOS/nixpkgs/nixos-unstable";
  };

  outputs = inputs@{ flake-parts, ... }:
    flake-parts.lib.mkFlake { inherit inputs; } {
      systems = [ "x86_64-linux" "aarch64-linux" "aarch64-darwin" "x86_64-darwin" ];
      perSystem = { config, self', inputs', pkgs, system, ... }:
        let
          terminus = pkgs.callPackage ./nix/package.nix { inherit pkgs; };
          terminus_dev = pkgs.callPackage ./nix/package-dev.nix { inherit pkgs; };
        in
          {
            # Programs: nix build
            # nix build .#terminus
            packages.terminus = terminus;

            # nix build .#dev
            packages.dev = terminus_dev;

            # nix build
            packages.default = self'.packages.terminus;

            # Apps: nix run
            # nix run .#terminus
            apps.terminus = {
              type = "app";
              program = self'.packages.terminus;
            };

            # nix run .#dev
            apps.dev = {
              type = "app";
              program = self'.packages.default;
            };

            # nix run
            apps.default = self'.apps.terminus;

            # Dev shells: nix develop
            # nix develop
            devShells.default = pkgs.mkShellNoCC {
              packages = with pkgs; [
                php
                git
                phpPackages.box
                phpPackages.composer
                openssh
                terminus_dev
              ];
            };
          };
    };
}
