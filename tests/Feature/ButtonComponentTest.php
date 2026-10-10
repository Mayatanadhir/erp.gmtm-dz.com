<?php

namespace Tests\Feature;

use Tests\TestCase;

class ButtonComponentTest extends TestCase
{
    public function test_edit_button_renders_as_link_with_icon_and_default_text(): void
    {
        $view = $this->blade('<x-edit-button href="/test/edit" />');

        $view->assertSee('href="/test/edit"', false);
        $view->assertSee('btn-edit', false);
        $view->assertSee('<svg', false);
        $view->assertSee(__('Edit'));
    }

    public function test_edit_button_renders_as_button_with_custom_slot(): void
    {
        $view = $this->blade('<x-edit-button type="button">Modify Record</x-edit-button>');

        $view->assertSee('type="button"', false);
        $view->assertSee('btn-edit', false);
        $view->assertSee('<svg', false);
        $view->assertSee('Modify Record');
    }

    public function test_edit_button_can_suppress_icon(): void
    {
        $view = $this->blade('<x-edit-button :icon="false">Custom Edit</x-edit-button>');

        $view->assertDontSee('<svg', false);
        $view->assertSee('Custom Edit');
        $view->assertSee('btn-edit', false);
    }

    public function test_primary_button_supports_both_button_and_link(): void
    {
        $button = $this->blade('<x-primary-button>Save</x-primary-button>');
        $button->assertSee('btn-primary', false);
        $button->assertSee('type="submit"', false);
        $button->assertSee('Save');

        $link = $this->blade('<x-primary-button href="/create">Add</x-primary-button>');
        $link->assertSee('btn-primary', false);
        $link->assertSee('href="/create"', false);
        $link->assertSee('Add');
    }

    public function test_secondary_button_supports_both_button_and_link(): void
    {
        $button = $this->blade('<x-secondary-button>Cancel</x-secondary-button>');
        $button->assertSee('btn-secondary', false);
        $button->assertSee('type="button"', false);

        $link = $this->blade('<x-secondary-button href="/back">Back</x-secondary-button>');
        $link->assertSee('btn-secondary', false);
        $link->assertSee('href="/back"', false);
    }

    public function test_danger_button_supports_both_button_and_link(): void
    {
        $button = $this->blade('<x-danger-button>Delete</x-danger-button>');
        $button->assertSee('btn-danger', false);
        $button->assertSee('type="submit"', false);

        $link = $this->blade('<x-danger-button href="/delete">Purge</x-danger-button>');
        $link->assertSee('btn-danger', false);
        $link->assertSee('href="/delete"', false);
    }

    public function test_success_button_supports_both_button_and_link(): void
    {
        $button = $this->blade('<x-success-button>Approve</x-success-button>');
        $button->assertSee('btn-success', false);
        $button->assertSee('type="submit"', false);

        $link = $this->blade('<x-success-button href="/download">Download</x-success-button>');
        $link->assertSee('btn-success', false);
        $link->assertSee('href="/download"', false);
    }

    public function test_info_button_supports_both_button_and_link(): void
    {
        $button = $this->blade('<x-info-button>Inspect</x-info-button>');
        $button->assertSee('btn-info', false);
        $button->assertSee('type="button"', false);

        $link = $this->blade('<x-info-button href="/inspect">View Details</x-info-button>');
        $link->assertSee('btn-info', false);
        $link->assertSee('href="/inspect"', false);
    }

    public function test_warning_button_supports_both_button_and_link(): void
    {
        $button = $this->blade('<x-warning-button>Retry</x-warning-button>');
        $button->assertSee('btn-warning', false);
        $button->assertSee('type="submit"', false);

        $link = $this->blade('<x-warning-button href="/retry">Pause</x-warning-button>');
        $link->assertSee('btn-warning', false);
        $link->assertSee('href="/retry"', false);
    }

    public function test_table_action_buttons_render_with_soft_colors_and_hover_states(): void
    {
        $edit = $this->blade('<x-table.action-edit href="/missions/1/edit" />');
        $edit->assertSee('href="/missions/1/edit"', false);
        $edit->assertSee('bg-amber-50', false);
        $edit->assertSee('text-amber-700', false);
        $edit->assertSee('hover:bg-amber-500', false);
        $edit->assertSee('hover:text-white', false);

        $delete = $this->blade('<x-table.action-delete action-url="/missions/1" item-name="M-01" />');
        $delete->assertSee('bg-rose-50', false);
        $delete->assertSee('text-rose-700', false);
        $delete->assertSee('hover:bg-rose-600', false);

        $view = $this->blade('<x-table.action-view href="/missions/1" />');
        $view->assertSee('bg-indigo-50', false);
        $view->assertSee('text-indigo-700', false);
        $view->assertSee('hover:bg-indigo-600', false);
    }
}
