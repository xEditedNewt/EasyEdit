<?php

namespace platz1de\EasyEdit\world\clientblock;

use pocketmine\scheduler\AsyncTask;

class RegistryUpdateTask extends AsyncTask
{
	public function onRun(): void
	{
		// CompoundBlock is only used in the main thread to send fake blocks to clients.
		// Registering its serializer or runtime registry here conflicts with other plugins
		// that also call BlockTypeIds::newId() in async workers (the counter resets per thread).
	}
}
