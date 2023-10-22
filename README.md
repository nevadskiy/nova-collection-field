# Collection fields for Laravel Nova

## Usage

### OneToManyCollection

`FaqSection` resource:

```php
namespace App\Nova;

use App\Models\FaqSection as FaqSectionModel;
use App\Nova\Resource;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Nevadskiy\Nova\Collection\OneToManyCollection;

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

            OneToManyCollection::make('Questions', 'items', FaqItem::class)
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

### ManyToAnyCollection

Usage example for a `Page` model that has defined [Many-To-Many (Polymorphic)](https://laravel.com/docs/10.x/eloquent-relationships#many-to-many-polymorphic-relations) relations. 

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

            MorphToManyCollection::make('Sections')
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
        return $this->morphedByMany(HeroSection::class, 'page_section')
            ->withPivot('position')
            ->withTimestamps();
    }

    public function demoSections(): MorphToMany
    {
        return $this->morphedByMany(DemoSection::class, 'page_section')
            ->withPivot('position')
            ->withTimestamps();
    }

    public function faqSections(): MorphToMany
    {
        return $this->morphedByMany(FaqSection::class, 'page_section')
            ->withPivot('position')
            ->withTimestamps();
    }
}
```
