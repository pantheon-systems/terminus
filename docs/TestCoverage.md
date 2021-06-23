---
# Terminus Test Coverage #


********************************************************************************
* Once upon a time, terminus had unit tests and those unit tests passed.       *
* Somewhere in it’s history those unit tests were disabled and no longer       *
* worked. For v2.6 we have a single functional test running and because there  *
* hasn’t been much development in Terminus this has sufficed. With this much   *
* change in Terminus we can’t let that continue. In order to have confidence   *
* in this release and move forward with development of the product, We need a  *
* full suite of functional tests.  I’ve written a bunch of new tests and       *
* committed a coverage page keeping track of what’s been written,              *
* what’s passing and what is lacking. This page should be updated with every   *
* commit.                                                                      *
********************************************************************************

Legend: ✅ Pass     💩 Bad test     🤮 Exception     ❌ Fail️️     ⚠️ Warning     ❓ Not Written

| aliasescommand                           |            | 
| :---                                     |   :---:    | 
| Aliases                                  | ❓        | 
| :---                                     |   :---:    | 
| artcommand                               |            | 
| :---                                     |   :---:    | 
| Art                                      | ❓        | 
| :---                                     |   :---:    | 
| auth                                     |            | 
| :---                                     |   :---:    | 
| Auth\Whoami                              | ❓        | 
| Auth\Login                               | ❓        | 
| Auth\Logout                              | ❓        | 
| :---                                     |   :---:    | 
| backup                                   |            | 
| :---                                     |   :---:    | 
| Backup\Get                               | ✅        | 
| Backup\SingleBackup                      | ❓        | 
| Backup\Info                              | ✅        | 
| Backup\Restore                           | ❓        | 
| Backup\List                              | ✅        | 
| Backup\Automatic\Enable                  | ❓        | 
| Backup\Automatic\Info                    | ❓        | 
| Backup\Automatic\Disable                 | ❓        | 
| Backup\Create                            | ✅        | 
| Backup\Backup                            | ❓        | 
| :---                                     |   :---:    | 
| branch                                   |            | 
| :---                                     |   :---:    | 
| Branch\List                              | ❓        | 
| :---                                     |   :---:    | 
| connection                               |            | 
| :---                                     |   :---:    | 
| Connection\Info                          | ❓        | 
| Connection\Set                           | ❓        | 
| :---                                     |   :---:    | 
| d9ify                                    |            | 
| :---                                     |   :---:    | 
| D9ify\Process                            | ❓        | 
| :---                                     |   :---:    | 
| dashboard                                |            | 
| :---                                     |   :---:    | 
| Dashboard\View                           | ❓        | 
| :---                                     |   :---:    | 
| domain                                   |            | 
| :---                                     |   :---:    | 
| Domain\Lookup                            | ❓        | 
| Domain\Remove                            | ❓        | 
| Domain\DNS                               | ❓        | 
| Domain\Add                               | ❓        | 
| Domain\List                              | ❓        | 
| Domain\Primary\Remove                    | ❓        | 
| Domain\Primary\Add                       | ❓        | 
| :---                                     |   :---:    | 
| env                                      |            | 
| :---                                     |   :---:    | 
| Env\CodeLog                              | ✅        | 
| Env\Commit                               | ❓        | 
| Env\Info                                 | ✅        | 
| Env\Metrics                              | ✅        | 
| Env\DiffStat                             | ✅        | 
| Env\List                                 | ✅        | 
| Env\Deploy                               | ❓        | 
| Env\Wipe                                 | ❓        | 
| Env\Wake                                 | ❓        | 
| Env\ClearCache                           | ✅        | 
| Env\CloneContent                         | ❓        | 
| Env\View                                 | ❓        | 
| :---                                     |   :---:    | 
| https                                    |            | 
| :---                                     |   :---:    | 
| HTTPS\Info                               | ❓        | 
| HTTPS\Set                                | ❓        | 
| HTTPS\Remove                             | ❓        | 
| :---                                     |   :---:    | 
| import                                   |            | 
| :---                                     |   :---:    | 
| Import\Database                          | ❓        | 
| Import\Site                              | ❓        | 
| Import\Complete                          | ❓        | 
| Import\Files                             | ❓        | 
| :---                                     |   :---:    | 
| local                                    |            | 
| :---                                     |   :---:    | 
| Local\DownloadLiveDbBackup               | ❓        | 
| Local\Clone                              | ❓        | 
| Local\DownloadLiveFilesBackup            | ❓        | 
| Local\CommitAndPush                      | ❓        | 
| :---                                     |   :---:    | 
| lock                                     |            | 
| :---                                     |   :---:    | 
| Lock\Enable                              | ❓        | 
| Lock\Info                                | ❓        | 
| Lock\Disable                             | ❓        | 
| :---                                     |   :---:    | 
| machinetoken                             |            | 
| :---                                     |   :---:    | 
| MachineToken\DeleteAll                   | ❓        | 
| MachineToken\List                        | ❓        | 
| MachineToken\Delete                      | ❓        | 
| :---                                     |   :---:    | 
| multidev                                 |            | 
| :---                                     |   :---:    | 
| Multidev\List                            | ❓        | 
| Multidev\Create                          | ❓        | 
| Multidev\Delete                          | ❓        | 
| Multidev\MergeToDev                      | ❓        | 
| Multidev\MergeFromDev                    | ❓        | 
| :---                                     |   :---:    | 
| newrelic                                 |            | 
| :---                                     |   :---:    | 
| NewRelic\Enable                          | ❓        | 
| NewRelic\Info                            | ❓        | 
| NewRelic\Disable                         | ❓        | 
| :---                                     |   :---:    | 
| org                                      |            | 
| :---                                     |   :---:    | 
| Org\List                                 | ✅        | 
| Org\People\Remove                        | ❓        | 
| Org\People\Add                           | ❓        | 
| Org\People\Role                          | ❓        | 
| Org\People\List                          | ✅        | 
| Org\Site\Remove                          | ❓        | 
| Org\Site\List                            | ✅        | 
| Org\Upstream\List                        | ✅        | 
| :---                                     |   :---:    | 
| owner                                    |            | 
| :---                                     |   :---:    | 
| Owner\Set                                | ❓        | 
| :---                                     |   :---:    | 
| paymentmethod                            |            | 
| :---                                     |   :---:    | 
| PaymentMethod\Remove                     | ❓        | 
| PaymentMethod\Add                        | ❓        | 
| PaymentMethod\List                       | ❓        | 
| :---                                     |   :---:    | 
| plan                                     |            | 
| :---                                     |   :---:    | 
| Plan\Info                                | ❓        | 
| Plan\Set                                 | ❓        | 
| Plan\List                                | ❓        | 
| :---                                     |   :---:    | 
| redis                                    |            | 
| :---                                     |   :---:    | 
| Redis\Enable                             | ❓        | 
| Redis\Disable                            | ❓        | 
| :---                                     |   :---:    | 
| remote                                   |            | 
| :---                                     |   :---:    | 
| Remote\SSHBase                           | ❓        | 
| Remote\Drush                             | ❓        | 
| Remote\WP                                | ❓        | 
| :---                                     |   :---:    | 
| self                                     |            | 
| :---                                     |   :---:    | 
| Self\ConfigDump                          | ❓        | 
| Self\Info                                | ❓        | 
| Self\Console                             | ❓        | 
| Self\ClearCache                          | ❓        | 
| :---                                     |   :---:    | 
| servicelevel                             |            | 
| :---                                     |   :---:    | 
| ServiceLevel\Set                         | ❓        | 
| :---                                     |   :---:    | 
| site                                     |            | 
| :---                                     |   :---:    | 
| Site\Lookup                              | ❓        | 
| Site\Info                                | ✅        | 
| Site\Site                                | ❓        | 
| Site\Org\Remove                          | ❓        | 
| Site\Org\Add                             | ❓        | 
| Site\Org\List                            | ✅        | 
| Site\List                                | ✅        | 
| Site\Team\Remove                         | ❓        | 
| Site\Team\Add                            | ❓        | 
| Site\Team\Role                           | ❓        | 
| Site\Team\List                           | ❓        | 
| Site\Create                              | ❓        | 
| Site\Delete                              | ❓        | 
| Site\Upstream\Set                        | ❓        | 
| Site\Upstream\ClearCache                 | ❓        | 
| :---                                     |   :---:    | 
| solr                                     |            | 
| :---                                     |   :---:    | 
| Solr\Enable                              | ❓        | 
| Solr\Disable                             | ❓        | 
| :---                                     |   :---:    | 
| sshkey                                   |            | 
| :---                                     |   :---:    | 
| SSHKey\Remove                            | ❓        | 
| SSHKey\Add                               | ❓        | 
| SSHKey\List                              | ❓        | 
| :---                                     |   :---:    | 
| tag                                      |            | 
| :---                                     |   :---:    | 
| Tag\Remove                               | ❓        | 
| Tag\Add                                  | ❓        | 
| Tag\List                                 | ❓        | 
| Tag\Tag                                  | ❓        | 
| :---                                     |   :---:    | 
| terminuscommand                          |            | 
| :---                                     |   :---:    | 
| Terminus                                 | ❓        | 
| :---                                     |   :---:    | 
| upstream                                 |            | 
| :---                                     |   :---:    | 
| Upstream\Info                            | ✅        | 
| Upstream\Updates\Updates                 | ❓        | 
| Upstream\Updates\List                    | ❓        | 
| Upstream\Updates\Apply                   | ❓        | 
| Upstream\Updates\Status                  | ❓        | 
| Upstream\List                            | ✅        | 
| :---                                     |   :---:    | 
| workflow                                 |            | 
| :---                                     |   :---:    | 
| Workflow\List                            | ❓        | 
| Workflow\Info\Logs                       | ❓        | 
| Workflow\Info\Operations                 | ❓        | 
| Workflow\Info\Status                     | ❓        | 
| Workflow\Info\InfoBase                   | ❓        | 
| Workflow\Watch                           | ❓        | 
| :---                                     |   :---:    | 
