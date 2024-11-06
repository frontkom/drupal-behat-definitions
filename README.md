# drupal-behat-definitions
Useful Behat things we use for Drupal

## Installation

```bash
composer require --dev frontkom/drupal-behat-definitions
```

Then you would probably include the contexts you are interested in, inside of your `behat.yml` file. Here is one example of including the Drupal Gutenberg Context:

```diff
       contexts:
+        - Frontkom\DrupalBehatDefinitions\DrupalGutenbergContext
         - Drupal\DrupalExtension\Context\MinkContext
         - Drupal\DrupalExtension\Context\MarkupContext
         - Drupal\DrupalExtension\Context\MessageContext
```
