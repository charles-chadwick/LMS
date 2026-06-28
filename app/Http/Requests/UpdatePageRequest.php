<?php

namespace App\Http\Requests;

/**
 * Pages have no unique constraints, so updating uses the same rules as storing.
 */
class UpdatePageRequest extends StorePageRequest {}
