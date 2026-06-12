<?php

declare(strict_types=1);

namespace Prestoworld\SearchEngine\Tests\UI;

use PHPUnit\Framework\TestCase;
use Prestoworld\SearchEngine\UI\SearchForm;

class SearchFormTest extends TestCase
{
    private SearchForm $form;

    protected function setUp(): void
    {
        $this->form = new SearchForm('/search', 'GET');
    }

    public function test_constructor_sets_action_and_method(): void
    {
        $html = $this->form->render();
        $this->assertStringContainsString('action="/search"', $html);
        $this->assertStringContainsString('method="GET"', $html);
    }

    public function test_create_static_factory(): void
    {
        $form = SearchForm::create('/test', 'POST');
        $html = $form->render();

        $this->assertStringContainsString('action="/test"', $html);
        $this->assertStringContainsString('method="POST"', $html);
    }

    public function test_action_fluent(): void
    {
        $this->form->action('/new-search');
        $html = $this->form->render();

        $this->assertStringContainsString('action="/new-search"', $html);
    }

    public function test_method_fluent(): void
    {
        $this->form->method('POST');
        $html = $this->form->render();

        $this->assertStringContainsString('method="POST"', $html);
    }

    public function test_attributes(): void
    {
        $this->form->attributes(['id' => 'search-form', 'data-ajax' => 'true']);
        $html = $this->form->render();

        $this->assertStringContainsString('id="search-form"', $html);
        $this->assertStringContainsString('data-ajax="true"', $html);
    }

    public function test_add_class(): void
    {
        $this->form->addClass('custom-class');
        $html = $this->form->render();

        $this->assertStringContainsString('class="custom-class"', $html);
    }

    public function test_add_class_appends(): void
    {
        $this->form->attributes(['class' => 'base-class']);
        $this->form->addClass('extra-class');
        $html = $this->form->render();

        $this->assertStringContainsString('class="base-class extra-class"', $html);
    }

    public function test_css_classes(): void
    {
        $this->form->cssClasses(['button' => 'btn-primary', 'input' => 'form-control']);
        $html = $this->form->render();

        $this->assertStringContainsString('class="btn-primary"', $html);
    }

    public function test_default_form_has_search_input(): void
    {
        $html = $this->form->render();
        $this->assertStringContainsString('<form', $html);
        $this->assertStringContainsString('</form>', $html);
    }

    public function test_query_field_customization(): void
    {
        $this->form->queryField('query', 'test-value');
        $html = $this->form->render();

        $this->assertStringContainsString('name="query"', $html);
        $this->assertStringContainsString('value="test-value"', $html);
    }

    public function test_text_filter(): void
    {
        $this->form->textFilter('author', ['label' => 'Author', 'placeholder' => 'Search author...', 'value' => 'John']);
        $html = $this->form->render();

        $this->assertStringContainsString('Author', $html);
        $this->assertStringContainsString('name="author"', $html);
        $this->assertStringContainsString('value="John"', $html);
    }

    public function test_select_filter(): void
    {
        $this->form->selectFilter('category', ['1' => 'Tech', '2' => 'Science'], ['value' => '1']);
        $html = $this->form->render();

        $this->assertStringContainsString('<select', $html);
        $this->assertStringContainsString('Tech', $html);
        $this->assertStringContainsString('selected', $html);
    }

    public function test_multi_select_filter(): void
    {
        $this->form->multiSelectFilter('tags', ['php' => 'PHP', 'js' => 'JavaScript'], ['value' => ['php']]);
        $html = $this->form->render();

        $this->assertStringContainsString('type="checkbox"', $html);
        $this->assertStringContainsString('checked', $html);
    }

    public function test_range_filter(): void
    {
        $this->form->rangeFilter('price', ['min' => 10, 'max' => 100]);
        $html = $this->form->render();

        $this->assertStringContainsString('name="price_min"', $html);
        $this->assertStringContainsString('name="price_max"', $html);
        $this->assertStringContainsString('value="10"', $html);
        $this->assertStringContainsString('value="100"', $html);
    }

    public function test_date_range_filter(): void
    {
        $this->form->dateRangeFilter('created_at', ['min' => '2024-01-01', 'max' => '2024-12-31']);
        $html = $this->form->render();

        $this->assertStringContainsString('type="date"', $html);
        $this->assertStringContainsString('name="created_at_min"', $html);
        $this->assertStringContainsString('name="created_at_max"', $html);
    }

    public function test_checkbox_filter(): void
    {
        $this->form->checkboxFilter('is_featured', ['checked' => true]);
        $html = $this->form->render();

        $this->assertStringContainsString('type="checkbox"', $html);
        $this->assertStringContainsString('checked', $html);
    }

    public function test_sort_select(): void
    {
        $this->form->sortSelect([
            'created_at' => 'Newest',
            'price' => 'Price',
        ], ['value' => 'created_at']);
        $html = $this->form->render();

        $this->assertStringContainsString('Sort by', $html);
        $this->assertStringContainsString('Newest', $html);
        $this->assertStringContainsString('Price', $html);
    }

    public function test_hidden_field(): void
    {
        $this->form->hiddenField('user_id', '42');
        $html = $this->form->render();

        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString('name="user_id"', $html);
        $this->assertStringContainsString('value="42"', $html);
    }

    public function test_reset_button_shown_by_default(): void
    {
        $html = $this->form->render();
        $this->assertStringContainsString('type="reset"', $html);
        $this->assertStringContainsString('Reset', $html);
    }

    public function test_reset_button_hidden(): void
    {
        $this->form->resetButton(false);
        $html = $this->form->render();

        $this->assertStringNotContainsString('type="reset"', $html);
    }

    public function test_submit_button_always_present(): void
    {
        $html = $this->form->render();
        $this->assertStringContainsString('type="submit"', $html);
        $this->assertStringContainsString('Search', $html);
    }

    public function test_auto_submit_includes_javascript(): void
    {
        $this->form->autoSubmit(true);
        $html = $this->form->render();

        $this->assertStringContainsString('<script>', $html);
        $this->assertStringContainsString('form.addEventListener', $html);
    }

    public function test_auto_submit_off_excludes_javascript(): void
    {
        $this->form->autoSubmit(false);
        $html = $this->form->render();

        $this->assertStringNotContainsString('<script>', $html);
    }

    public function test_get_filter_manager(): void
    {
        $filterManager = $this->form->getFilterManager();
        $this->assertInstanceOf(\Prestoworld\SearchEngine\Filters\FilterManager::class, $filterManager);
    }

    public function test_get_sort_manager(): void
    {
        $sortManager = $this->form->getSortManager();
        $this->assertInstanceOf(\Prestoworld\SearchEngine\Sorting\SortManager::class, $sortManager);
    }

    public function test_render_returns_html_string(): void
    {
        $html = $this->form->render();
        $this->assertIsString($html);
        $this->assertStringStartsWith('<form', $html);
        $this->assertStringEndsWith('</form>', $html);
    }

    public function test_method_is_uppercased(): void
    {
        $this->form->method('post');
        $html = $this->form->render();

        $this->assertStringContainsString('method="POST"', $html);
    }

    public function test_form_has_search_input_by_default(): void
    {
        $html = $this->form->render();

        $this->assertStringContainsString('type="text"', $html);
        $this->assertStringContainsString('placeholder="Search..."', $html);
    }

    public function test_form_actions_div(): void
    {
        $html = $this->form->render();
        $this->assertStringContainsString('form-actions', $html);
    }

    public function test_default_css_classes(): void
    {
        $html = $this->form->render();

        $this->assertStringContainsString('search-form', $html);
        $this->assertStringContainsString('search-input', $html);
        $this->assertStringContainsString('search-button', $html);
    }
}
