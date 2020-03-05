# ClassFinder
[![Build Status](https://travis-ci.org/cruxinator/ClassFinder.svg?branch=master)](https://travis-ci.org/cruxinator/ClassFinder)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/cruxinator/ClassFinder/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/cruxinator/ClassFinder/?branch=master)
[![Coverage Status](https://coveralls.io/repos/github/cruxinator/ClassFinder/badge.svg?branch=master)](https://coveralls.io/github/cruxinator/ClassFinder?branch=master)
[![Latest Stable Version](https://poser.pugx.org/cruxinator/class-finder/v/stable)](https://packagist.org/packages/cruxinator/class-finder)
[![Latest Unstable Version](https://poser.pugx.org/cruxinator/class-finder/v/unstable)](https://packagist.org/packages/cruxinator/class-finder)
[![Total Downloads](https://poser.pugx.org/cruxinator/class-finder/downloads)](https://packagist.org/packages/cruxinator/class-finder)
[![Monthly Downloads](https://poser.pugx.org/cruxinator/class-finder/d/monthly)](https://packagist.org/packages/cruxinator/class-finder)
[![Daily Downloads](https://poser.pugx.org/cruxinator/class-finder/d/daily)](https://packagist.org/packages/cruxinator/class-finder)

#ClassFinder

Turbocharged version of get_declared_classes that gets classes from the autoloader as well as memory.

To install, run
```
composer require cruxinator/cruxinator/class-finder
```

To take advantage of the extended class finder:

```
$classList = \Cruxinator\ClassFinder\ClassFinder::getClasses();
```

-- Known Limitations --
* Conditionals can only execute after classes are loaded