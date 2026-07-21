<?php

declare(strict_types=1);



namespace Kint\Value;

use DateTimeInterface;
use Kint\Value\Context\ContextInterface;

class DateTimeValue extends InstanceValue
{
    
    protected DateTimeInterface $dt;

    public function __construct(ContextInterface $context, DateTimeInterface $dt)
    {
        parent::__construct($context, \get_class($dt), \spl_object_hash($dt), \spl_object_id($dt));

        $this->dt = clone $dt;
    }

    public function getHint(): string
    {
        return parent::getHint() ?? 'datetime';
    }

    public function getDisplayValue(): string
    {
        $stamp = $this->dt->format('Y-m-d H:i:s');
        if ((int) ($micro = $this->dt->format('u'))) {
            $stamp .= '.'.$micro;
        }
        $stamp .= $this->dt->format(' P');

        $tzn = $this->dt->getTimezone()->getName();
        if ('+' !== $tzn[0] && '-' !== $tzn[0]) {
            $stamp .= $this->dt->format(' T');
        }

        return $stamp;
    }
}
