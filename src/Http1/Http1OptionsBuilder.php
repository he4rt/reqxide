<?php

declare(strict_types=1);

namespace Reqxide\Http1;

final class Http1OptionsBuilder
{
    private ?OriginalHeaderMap $originalHeaderMap = null;

    public function originalHeaderMap(OriginalHeaderMap $map): self
    {
        $this->originalHeaderMap = $map;

        return $this;
    }

    public function build(): Http1Options
    {
        return new Http1Options(
            originalHeaderMap: $this->originalHeaderMap,
        );
    }
}
