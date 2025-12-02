<?php

namespace App\View\Composers;

use App\Services\BiblioCommonsTemplateService;
use Illuminate\View\View;

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
