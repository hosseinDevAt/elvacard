<?php

namespace App\Exceptions;

use InvalidArgumentException;

/**
 * Thrown at draft-order time when the authoritative database price of a cart
 * line no longer matches the price snapshot the customer saw. The checkout is
 * rejected (no order is created, nothing is charged) and the stored session
 * snapshots are refreshed so the next attempt shows the updated amount.
 */
class CartPriceChangedException extends InvalidArgumentException {}
