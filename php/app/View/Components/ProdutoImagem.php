<?php

namespace App\View\Components;

use Illuminate\View\Component;

class ProdutoImagem extends Component
{
    public string $src;
    public string $alt;
    public string $classes;

    public function __construct(
        ?string $src = null,
        string $alt = 'Produto',
        string $classes = 'w-16 h-16 rounded-lg object-cover',
    ) {
        $this->alt = $alt;
        $this->classes = $classes;
        $this->src = $this->resolveSrc($src);
    }

    private function resolveSrc(?string $src): string
    {
        if (!$src || !filter_var($src, FILTER_VALIDATE_URL)) {
            return 'https://placehold.co/128x128/e5e7eb/9ca3af?text=Sem+Foto';
        }
        return $src;
    }

    public function render(): string
    {
        return <<<'blade'
            <img src="{{ $src }}" alt="{{ $alt }}" class="{{ $classes }}"
                 onerror="this.src='https://placehold.co/128x128/e5e7eb/9ca3af?text=Sem+Foto'">
        blade;
    }
}
