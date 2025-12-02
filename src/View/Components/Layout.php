<?php

namespace Tpl\Shared\View\Components;

use Illuminate\View\Component;

class Layout extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(public bool $center = false) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): string
    {
        return 'tpl-shared::components.layout';
    }
}
