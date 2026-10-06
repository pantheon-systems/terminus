{
  inputs,
  ...
}:
{
  imports = [
    inputs.flake-parts.flakeModules.easyOverlay
    inputs.pkgs-by-name-for-flake-parts.flakeModule
  ];

  perSystem =
    { system, config, ... }:
    {
      pkgsDirectory = ../pkgs;

      _module.args.pkgs = import inputs.nixpkgs {
        inherit system;
        overlays = [
          (final: prev: {
            local = config.packages;
          })
        ];
      };

      overlayAttrs = config.packages;

      packages.default = config.packages.terminus;
    };
}
