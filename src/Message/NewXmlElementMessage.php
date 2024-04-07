<?php

declare(strict_types=1);

namespace App\Message;

use App\DTO\XmlElement;

final readonly class NewXmlElementMessage
{
    public function __construct(public XmlElement $element) {}
}
