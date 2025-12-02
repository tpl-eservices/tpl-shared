<?php

namespace Tpl\Shared\View\Composers;

use Illuminate\View\View;
use Tpl\Shared\Services\BiblioCommonsTemplateService;

class BiblioCommonsComposer
{
    /**
     * Create a new class instance.
     */
    public function __construct(protected BiblioCommonsTemplateService $templateService) {}

    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $view->with('bibliocommons', $this->templateService->getTemplateParts());
    }
}
