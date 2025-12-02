<?php

namespace Tpl\Shared\View\Components;

use Illuminate\View\Component;

class StaticLayout extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(public ?string $class = null)
    {
        $this->class = $class ?? 'max-w-4xl mx-auto';
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): string
    {
        return 'tpl-shared::components.static-layout';
    }
}
