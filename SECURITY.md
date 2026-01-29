# terminus : Security Considerations

The Pantheon Terminus Command-Line Interface is by design a secure and reliable way
to interact with and automate operation on the Pantheon platform. Commands are always
sent using TLS encryption over HTTPS, or via ssh in some instances. In order to have
the best possible experience with Terminus, and maintain the security of your
site assets, you should always follow basic security best practices when using Terminus.

## Machine Tokens

Always keep your [machine tokens](https://docs.pantheon.io/machine-tokens) secure.
If you expose a machine token in a location where an outside party can observe it,
anyone who uses that token can act with the privileges of the user that created it.
Always [revoke](https://docs.pantheon.io/machine-tokens#revoke-a-machine-token) any
machine token that is compromised.

## Plugins

Terminus plugins should only be used from trusted sources. Pantheon only endorses
plugins published in the pantheon-systems Github organization.

## Composer Dependencies

Terminus, and by extension, Terminus plugins, rely on Open Source libraries to perform
its functions. Whenever a vulnerability is discovered in one of these libraries, and
a remediation is published, Pantheons automated security automation will raise a
notification, and a new release of Terminus will be released. Note that most conceivable
dependency vulnerabilities are not a concern for Terminus users, as there is no
mechanism for an outside party to send commands to Terminus; therefore, there should
not be an attack vector inside Terminus that could be leveraged via a published
security vulnerability. Pantheon still recommends that all users upgrade to the latest
available Terminus release, to ensure the reliability and security of your infrastructure.

During plugin installation and updates, Terminus uses Composer to update dependencies
for the plugin. The default behavior of Composer checks all dependencies against
the published list of known security vulnerabilities, and will cause the plugin
operation to fail if any insecure dependencies exist. Pantheon has disabled this
behavior for Terminus and Terminus plugins by setting `config.audit.block-insecure` to
`false` in Terminus' composer.json file. This allows customers Continuous Integration
workflows to keep working, even if they need to install Terminus plugins with
dependencies with published security advisories. 

Use `terminus self:update` to update Terminus to the latest available version.
