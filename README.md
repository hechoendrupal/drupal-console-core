# Drupal Console Core

Drupal Console Core, this project contains commands and features to be shared across projects.

### Relocate commands:
#### Completed
```
* about                 Display basic information about project.
* check                 System requirement checker
* help                  Displays help for a command.
* list                  Lists all available commands.
* settings:debug        List user Drupal Console settings.
* settings:set          Change a specific setting value in DrupalConsole config file
```

#### Pending
```
* init                  Copy configuration files.
* site:debug            List all known local and remote sites.
* site:import:local     Import/Configure an existing local Drupal project
* translation:cleanup   Clean up translation files
* translation:pending   Determine pending translation string in a language or a specific file in a language
* translation:stats     Generate translate stats
* translation:sync      Sync translation files
* yaml:diff             Compare two YAML files in order to find differences between them.
* yaml:merge            Merge two or more YAML files in a new YAML file. Latest values are preserved.
* yaml:split            Split a YAML file using indent as separator criteria
* yaml:update:key       Replace a YAML key in a YAML file.
* yaml:update:value     Update a value for a specific key in a YAML file.
```