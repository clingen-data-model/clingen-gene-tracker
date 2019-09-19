<?php

namespace App;

final class HgncRecord extends ExternalServiceRecord
{
    public function getHgncId() {
        return (int)substr($this->attributes->hgnc_id, 5);
    }
}
