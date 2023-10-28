# Collection fields for Laravel Nova

## Usage

### HasManyCollection

`FaqSection` resource:

```php
namespace App\Nova;

use App\Models\FaqSection as FaqSectionModel;
use App\Nova\Resource;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Nevadskiy\Nova\Collection\HasManyCollection;

class FaqSection extends Resource
{
    public static string $model = FaqSectionModel::class;

    public static $title = 'heading';

    public static $search = [
        'heading',
    ];

    public function fields(NovaRequest $request): array
    {
        return [
            ID::make(),

            Text::make('Heading'),

            HasManyCollection::make('Questions', 'items', FaqItem::class)
                ->sortBy('position')
                ->stacked()
                ->fullWidth()
        ];
    }
}
```

`FaqSection` model:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FaqSection extends Model
{
    public function items(): HasMany
    {
        return $this->hasMany(FaqItem::class);
    }
}
```

### MorphToManyCollection

Usage example for a `Page` model that has defined [Many-To-Many (Polymorphic)](https://laravel.com/docs/eloquent-relationships#many-to-many-polymorphic-relations) relations. 

`Page` resource:

```php
namespace App\Nova;

use App\Models\Page as PageModel;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Nevadskiy\Nova\Collection\MorphToManyCollection;

class Page extends Resource
{
    public static string $model = PageModel::class;

    public function fields(NovaRequest $request): array
    {
        return [
            ID::make(),

            Text::make('Title'),

            MorphToManyCollection::make('Components')
                ->resources([
                    'heroSections' => HeroSection::class,
                    'demoSections' => DemoSection::class,
                    'faqSections' => FaqSection::class,
                ])
                ->sortBy('position')
                ->attachable()
                ->collapsable()
                ->stacked()
                ->fullWidth(),
        ];
    }
}
```

`Page` model:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Page extends Model
{
    public function heroSections(): MorphToMany
    {
        return $this->morphedByMany(HeroSection::class, 'page_component')
            ->withPivot('position');
    }

    public function demoSections(): MorphToMany
    {
        return $this->morphedByMany(DemoSection::class, 'page_component')
            ->withPivot('position');
    }

    public function faqSections(): MorphToMany
    {
        return $this->morphedByMany(FaqSection::class, 'page_component')
            ->withPivot('position');
    }
}
```

### ManyToMorphCollection

If you do not want to define a separate relation for each model type, you can use `ManyToMorphCollection` field that requires a separate [ManyToMorph](https://github.com/nevadskiy/laravel-many-to-morph) relation.

Usage example for a `Page` model that has defined the Many-To-Morph relation.

`Page` resource:

```php
namespace App\Nova;

use App\Models\Page as PageModel;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Nevadskiy\Nova\Collection\MorphToManyCollection;

class Page extends Resource
{
    public static string $model = PageModel::class;

    public function fields(NovaRequest $request): array
    {
        return [
            ID::make(),

            Text::make('Title'),

            ManyToMorphCollection::make('Components')
                ->resources([
                    HeroSection::class,
                    DemoSection::class,
                    FaqSection::class,
                ])
                ->sortBy('position')
                ->attachable()
                ->collapsable()
                ->stacked()
                ->fullWidth(),
        ];
    }
}
```

`Page` model:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Nevadskiy\ManyToMorph\HasManyToMorph;
use Nevadskiy\ManyToMorph\ManyToMorph;

class Page extends Model
{
    use HasManyToMorph;

    public function components(): ManyToMorph
    {
        return $this->manyToMorph('page_component');
    }
}
```
