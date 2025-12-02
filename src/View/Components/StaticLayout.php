<?php

namespace Tpl\Shared\View\Components;

use Illuminate\View\Component;

class StaticLayout extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct() {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): string
    {
        return 'tpl-shared::components.static-layout';
    }
}
