## Functional test fixutres

These fixtures are used by the functional tests.

### Plugins

By default, plugins from the path `fixtures/functional/plugins/default` are loaded for every call to Terminus made during the Behat tests.

To do functional tests with a different set of plugins, use:

    When I am using 'some-label' plugins

This will cause plugins to be loaded from the path `fixtures/functional/plugins/some-label` instead.
