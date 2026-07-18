{ php, lib, ... }:
let
  fs = lib.fileset;
  sourceFiles = ../../../.;
in
php.buildComposerProject2 {
  pname = "terminus";
  version = "4.3.3";

  src = fs.toSource {
    root = sourceFiles;
    fileset = sourceFiles;
  };

  vendorHash = "sha256-66cF1H5LbFuPnzN7i/ml0erpGaVfYZ5NeXfd+fteodA=";

  meta = {
    mainProgram = "terminus";
  };
}
