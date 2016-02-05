# netmarket-data-parsers
A set of classes aimed to parse unstructured text goods characteristics to numeric properies and tags.

It's wrote for yii2 framefork to be handled via it's console commands.

It's not ideal from performance perspective, like it can't be runned in parallel processed but it satisfy and meet all project need on existed data volume.

ItemSpecController - a class to control over the parsers, see actionProcess() and processRowsBatch()

ItemListSpecController - a class to control over the 'terms' 

SpecParserProcessor - a main processor doing all the job

spec_parsers/TaxonomySpecParser - a parser class handling terms parsing

a set of classes located at spec_parsers folder doing the parsing job itself, a class per data type to parse
