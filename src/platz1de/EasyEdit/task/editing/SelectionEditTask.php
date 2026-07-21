<?php

namespace platz1de\EasyEdit\task\editing;

use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\result\EditTaskResult;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\identifier\SelectionIdentifier;
use platz1de\EasyEdit\selection\identifier\SelectionSerializer;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\task\ExecutableTask;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pmmp\thread\Thread;

/**
 * @extends ExecutableTask<EditTaskResult>
 */
abstract class SelectionEditTask extends ExecutableTask implements ChunkedTask
{
	protected SelectionContext $context;
	protected BlockListSelection $undo;
	protected EditTaskHandler $handler;

	/**
	 * @param SelectionIdentifier   $selection
	 * @param SelectionContext|null $context
	 */
	public function __construct(private SelectionIdentifier $selection, ?SelectionContext $context = null)
	{
		$this->context = $context ?? SelectionContext::full();
		parent::__construct();
	}

	/**
	 * @return EditTaskResult
	 */
	protected function executeInternal(): EditTaskResult
	{
		$handler = $this->getChunkHandler();
		EasyEdit::getEnv()->initChunkHandler($handler);
		$this->undo = $this->createUndoBlockList();
		$this->handler = new EditTaskHandler($this->getTargetWorld(), $this->undo);
		$chunks = $this->sortChunks($this->getSelection()->getNeededChunks());
		EasyEdit::getEnv()->executeChunkedTask($this, $handler, $this->handler, $chunks);

		if ($this->handler->getChangedBlockCount() === 0) {
			//Not debug: this always means something went wrong, and the player is told the task "succeeded" with 0 blocks
			$selection = $this->getSelection();
			$pos1 = $selection->getPos1();
			$pos2 = $selection->getPos2();
			EditThread::getInstance()->getLogger()->warning("Task " . $this->getTaskName() . ":" . $this->getTaskId() . " changed 0 blocks. " .
				"World '" . $selection->getWorldName() . "', " .
				"(" . $pos1->x . ", " . $pos1->y . ", " . $pos1->z . ") to (" . $pos2->x . ", " . $pos2->y . ", " . $pos2->z . "), " .
				count($chunks) . " chunks needed, " . $this->handler->getReadBlockCount() . " blocks read, " .
				"ran on " . (Thread::getCurrentThread() instanceof EditThread ? "edit thread" : "main thread"));
		}

		EditThread::getInstance()->debug("Task " . $this->getTaskName() . ":" . $this->getTaskId() . " was executed successful, changing " . $this->handler->getChangedBlockCount() . " blocks (" . $this->handler->getReadBlockCount() . " read, " . $this->handler->getWrittenBlockCount() . " written)");
		return $this->toTaskResult();
	}

	protected function toTaskResult(): EditTaskResult
	{
		return new EditTaskResult($this->handler->getChangedBlockCount(), $this->undo);
	}

	public function attemptRecovery(): EditTaskResult
	{
		return $this->toTaskResult();
	}

	/**
	 * @return BlockListSelection
	 */
	abstract public function createUndoBlockList(): BlockListSelection;

	public function canExecuteOnMainThread(): bool
	{
		return $this->getChunkHandler()->checkLoaded($this->getSelection());
	}

	/**
	 * This method may only shuffle the chunks, not add or remove any (otherwise stuff will break, beware!)
	 * @param int[] $chunks
	 * @return int[]
	 */
	protected function sortChunks(array $chunks): array
	{
		return $chunks;
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString(SelectionSerializer::fastSerialize($this->selection));
		$stream->putString($this->context->fastSerialize());
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->selection = SelectionSerializer::fastDeserialize($stream->getString());
		$this->context = SelectionContext::fastDeserialize($stream->getString());
	}

	/**
	 * @return Selection
	 */
	public function getSelection(): Selection
	{
		return $this->selection->asSelection();
	}

	/**
	 * @return GroupedChunkHandler
	 */
	protected function getChunkHandler(): GroupedChunkHandler
	{
		return new SingleChunkHandler($this->getTargetWorld());
	}

	/**
	 * @return string
	 */
	public function getTargetWorld(): string
	{
		return $this->selection->asSelection()->getWorldName();
	}
}