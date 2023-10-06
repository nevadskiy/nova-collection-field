# Collection fields for Laravel Nova

## Available fields

- [ ] OneToManyCollection
- [ ] ManyToManyCollection
- [ ] OneToAnyCollection
- [x] ManyToAnyCollection

## Usage

Usage example for a `Page` model that has defined [Many-To-Many (Polymorphic)](https://laravel.com/docs/10.x/eloquent-relationships#many-to-many-polymorphic-relations) relations. 

`Page` resource:

```php
namespace App\Nova;

use App\Models\Page as PageModel;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Nevadskiy\Nova\Collection\ManyToAnyCollection;

class Page extends Resource
{
    public static string $model = PageModel::class;

    public function fields(NovaRequest $request): array
    {
        return [
            ID::make(),

            Text::make('Title'),

            ManyToAnyCollection::make('Sections')
                ->resources([
                    HeroSection::class => 'heroSections',
                    DemoSection::class => 'demoSections',
                    FaqSection::class => 'faqSections',
                ])
                ->sortByPivot('position')
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
