<?php

declare(strict_types=1);



namespace CodeIgniter\Validation\StrictRules;

use CodeIgniter\Validation\CreditCardRules as NonStrictCreditCardRules;


class CreditCardRules
{
    private  NonStrictCreditCardRules $nonStrictCreditCardRules;

    public function __construct()
    {
        $this->nonStrictCreditCardRules = new NonStrictCreditCardRules();
    }

    
    public function valid_cc_number($ccNumber, string $type): bool
    {
        if (! is_string($ccNumber)) {
            return false;
        }

        return $this->nonStrictCreditCardRules->valid_cc_number($ccNumber, $type);
    }
}
