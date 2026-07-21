<?php

namespace platz1de\EasyEdit\world\blockupdate;

use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\UpdateSubChunkBlocksPacketEntry;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\network\mcpe\protocol\UpdateSubChunkBlocksPacket;

class InjectingData
{
	/**
	 * @var UpdateSubChunkBlocksPacketEntry[]
	 */
	private array $entries = [];
	private BlockPosition $position;

	public function __construct(int $x, int $y, int $z)
	{
		$this->position = new BlockPosition($x, $y, $z);
	}

	public function writeBlock(int $x, int $y, int $z, int $id): void
	{
		$this->entries[] = new UpdateSubChunkBlocksPacketEntry(
			new BlockPosition($x, $y, $z),
			TypeConverter::getInstance()->getBlockTranslator()->internalIdToNetworkId($id),
			UpdateBlockPacket::FLAG_NETWORK,
			0, //we don't have any actors
			0 //not synced
		);
	}

	/**
	 * This used to hand-encode the payload through PacketSerializer to avoid creating entry objects.
	 * That class was removed from BedrockProtocol, and the wire format is not ours to guess, so the
	 * real packet is built instead and the protocol library handles the encoding.
	 * @return UpdateSubChunkBlocksPacket
	 */
	public function toPacket(): UpdateSubChunkBlocksPacket
	{
		return UpdateSubChunkBlocksPacket::create($this->position, $this->entries, []); //we don't use the second layer
	}
}
