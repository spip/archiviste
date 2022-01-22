<?php

namespace Spip\Archiver;

class NoDotFilterIterator extends \FilterIterator
{
	public function accept(): bool {
		return !in_array($this->current()->getFilename(), ['.', '..']);
	}
}
