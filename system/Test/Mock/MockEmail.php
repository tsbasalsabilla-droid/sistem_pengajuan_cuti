<?php

declare(strict_types=1);



namespace CodeIgniter\Test\Mock;

use CodeIgniter\Email\Email;
use CodeIgniter\Events\Events;

class MockEmail extends Email
{
    
    public $returnValue = true;

    public function send($autoClear = true)
    {
        if ($this->returnValue) {
            $this->setArchiveValues();

            if ($autoClear) {
                $this->clear();
            }

            Events::trigger('email', $this->archive);
        }

        return $this->returnValue;
    }
}
