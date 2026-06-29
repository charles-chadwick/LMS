<?php

namespace App\Http\Requests;

/**
 * Updating a group validates exactly like creating one, so the rules,
 * attributes, and authorization are inherited from StoreGroupRequest.
 */
class UpdateGroupRequest extends StoreGroupRequest
{
}
