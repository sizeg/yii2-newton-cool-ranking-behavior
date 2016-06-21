# Yii2 Newton Cool Ranking Behavior

This behavior provides the algorithm of rank hotness with Newton's law of cooling
explained by [Evan Miller](http://www.evanmiller.org/).

You can use it to rate comments or blog posts. Listing active discussion threads in an online forum.

Read this article [Rank Hotness With Newton's Law of Cooling](http://www.evanmiller.org/rank-hotness-with-newtons-law-of-cooling.html) for more details.

## Installation

Package is available on [Packagist](https://packagist.org/packages/sizeg/yii2-newton-cool-ranking-behavior),
you can install it using [Composer](http://getcomposer.org).

```shell
composer require sizeg/yii2-newton-cool-ranking-behavior
```

### Dependencies

- Yii2 (testing with 2.8, but should work with lower versions)

## Basic usage

Create migration,

```php
public function up()
{
    // [[NewtonCoolRankingBehavior::$rankAttribute]]
    $this->addColumn('{{%tableName}}', 'rank', $this->float());

    // [[NewtonCoolRankingBehavior::$rankTimeAttribute]]
    // By default time update with result of php time() function
    // For example we will use DateTime instead of UnixTimestamp
    $this->addColumn('{{%tableName}}', 'rankTime', $this->datetime());

    // [[NewtonCoolRankingBehavior::$rankBoostAttribute]]
    // This field is optional
    $this->addField('{{%tableName}}', 'rankBoost', $this->float());
}

```

Add behavior to your ActiveRecord model,

```php
class Item extends \yii\base\ActiveRecord
{
    public function behaviors()
    {
        return \yii\helpers\ArrayHelper::merge(parent::behaviors(), [
            [
                'class' => 'sizeg\newtoncoolranking\NewtonCoolRankingBehavior',
                // optional params
                'initial' => 1000, 
                'coolingRate' => 150,
                'timeValue' => date('Y-m-d H:i:s'), // can be a callback function
            ]
        ]);
    }
}
```

By default the new model would have [[NewtonCoolRankingBehavior::$initial]] value
and will cooling with [[NewtonCoolRankingBehavior::$coolingRate]].

When there is new activity on an model, you need update rank,

```php
/** @var ActiveRecord $model */
$model->heat(20);
```

Sometimes you need one or more models to show in top for a few days, then you need to boost it.

Boost value will be received from model [[NewtonCoolRankingBehavior::$rankBoostAttribute]] field.
If field doesn't exist, the value will be received from optional [[NewtonCoolRankingBehavior::$boost]] attribute.

```php
/** @var ActiveRecord $model */
$model->boost();
```
