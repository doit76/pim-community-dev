<?php

namespace Akeneo\Bundle\BatchBundle\Tests\Unit\Step;

use Akeneo\Component\Batch\Event\EventInterface;
use Akeneo\Component\Batch\Item\InvalidItemException;
use Akeneo\Component\Batch\Job\BatchStatus;
use Akeneo\Component\Batch\Step\ItemStep;

/**
 * Tests related to the ItemStep class
 *
 */
class ItemStepTest extends \PHPUnit_Framework_TestCase
{
    const STEP_NAME = 'test_step_name';

    /**
     * @var ItemStep
     */
    protected $itemStep = null;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventDispatcher = null;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $jobRepository = null;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->eventDispatcher = $this->getMock('Symfony\\Component\\EventDispatcher\\EventDispatcherInterface');
        $this->jobRepository   = $this->getMock('Akeneo\\Component\\Batch\\Job\\JobRepositoryInterface');

        $this->itemStep = new ItemStep(self::STEP_NAME);

        $this->itemStep->setEventDispatcher($this->eventDispatcher);
        $this->itemStep->setJobRepository($this->jobRepository);
    }

    public function testGetConfiguration()
    {
        $reader    = $this->getReaderMock(array('reader_foo'       => 'bar'), array('reader_foo'));
        $processor = $this->getProcessorMock(array('processor_foo' => 'bar'), array('processor_foo'));
        $writer    = $this->getWriterMock(array('writer_foo'       => 'bar'), array('writer_foo'));

        $this->itemStep->setReader($reader);
        $this->itemStep->setProcessor($processor);
        $this->itemStep->setWriter($writer);

        $expectedConfiguration = array(
            'reader_foo'    => 'bar',
            'processor_foo' => 'bar',
            'writer_foo'    => 'bar',
        );

        $this->assertEquals($expectedConfiguration, $this->itemStep->getConfiguration());
    }

    public function testSetConfiguration()
    {
        $reader    = $this->getReaderMock(array(), array('reader_foo'));
        $processor = $this->getProcessorMock(array(), array('processor_foo'));
        $writer    = $this->getWriterMock(array(), array('writer_foo'));

        $this->itemStep->setReader($reader);
        $this->itemStep->setProcessor($processor);
        $this->itemStep->setWriter($writer);
        $config = array(
            'reader_foo'    => 'reader_bar',
            'processor_foo' => 'processor_bar',
            'writer_foo'    => 'writer_bar',
        );

        $reader->expects($this->once())
            ->method('setConfiguration')
            ->with($config);

        $processor->expects($this->once())
            ->method('setConfiguration')
            ->with($config);

        $writer->expects($this->once())
            ->method('setConfiguration')
            ->with($config);

        $this->itemStep->setConfiguration($config);
    }

    public function testExecute()
    {
        $stepExecution = $this->getMockBuilder('Akeneo\\Bundle\\BatchBundle\\Entity\\StepExecution')
            ->disableOriginalConstructor()
            ->getMock();
        $stepExecution->expects($this->any())
            ->method('getStatus')
            ->will($this->returnValue(new BatchStatus(BatchStatus::STARTING)));

        $reader = $this->getMockBuilder('Akeneo\\Bundle\\BatchBundle\\Tests\\Unit\\Step\\Stub\\ReaderStub')
            ->setMethods(array('setStepExecution', 'read', 'initialize', 'flush'))
            ->getMock();
        $reader->expects($this->once())
            ->method('setStepExecution')
            ->with($stepExecution);
        $reader->expects($this->exactly(8))
            ->method('read')
            ->will($this->onConsecutiveCalls(1, 2, 3, 4, 5, 6, 7, null));
        $reader->expects($this->once())->method('initialize');
        $reader->expects($this->once())->method('flush');

        $processor = $this->getMockBuilder('Akeneo\\Bundle\\BatchBundle\\Tests\\Unit\\Step\\Stub\\ProcessorStub')
            ->setMethods(array('setStepExecution', 'process', 'initialize', 'flush'))
            ->getMock();
        $processor->expects($this->once())
            ->method('setStepExecution')
            ->with($stepExecution);
        $processor->expects($this->exactly(7))
            ->method('process')
            ->will($this->onConsecutiveCalls(1, 2, 3, 4, 5, 6, 7));
        $processor->expects($this->once())->method('initialize');
        $processor->expects($this->once())->method('flush');

        $writer = $this->getMockBuilder('Akeneo\\Bundle\\BatchBundle\\Tests\\Unit\\Step\\Stub\\WriterStub')
            ->setMethods(array('setStepExecution', 'write', 'initialize', 'flush'))
            ->getMock();
        $writer->expects($this->once())
            ->method('setStepExecution')
            ->with($stepExecution);
        $writer->expects($this->exactly(2))
            ->method('write');
        $writer->expects($this->once())->method('initialize');
        $writer->expects($this->once())->method('flush');

        $this->itemStep->setReader($reader);
        $this->itemStep->setProcessor($processor);
        $this->itemStep->setWriter($writer);
        $this->itemStep->setBatchSize(5);
        $this->itemStep->execute($stepExecution);
    }

    public function testDispatchReadInvalidItemException()
    {
        $stepExecution = $this->getMockBuilder('Akeneo\\Bundle\\BatchBundle\\Entity\\StepExecution')
            ->disableOriginalConstructor()
            ->getMock();
        $stepExecution->expects($this->any())
            ->method('getStatus')
            ->will($this->returnValue(new BatchStatus(BatchStatus::STARTING)));
        $stepExecution->expects($this->any())
            ->method('getExitStatus')
            ->will($this->returnValue(new BatchStatus(BatchStatus::STARTED)));

        $this->eventDispatcher
            ->expects($this->at(1))
            ->method('dispatch')
            ->with(
                EventInterface::INVALID_ITEM,
                $this->logicalAnd(
                    $this->isInstanceOf('Akeneo\\Component\\Batch\\Event\\InvalidItemEvent'),
                    $this->attributeEqualTo('reason', 'The read item is invalid'),
                    $this->attributeEqualTo('item', array('foo' => 'bar'))
                )
            );

        $reader = $this->getMock('Akeneo\\Bundle\\BatchBundle\\Tests\\Unit\\Item\\ItemReaderTestHelper');
        $reader->expects($this->exactly(2))
            ->method('read')
            ->will(
                $this->onConsecutiveCalls(
                    $this->throwException(new InvalidItemException('The read item is invalid', array('foo' => 'bar'))),
                    $this->returnValue(null)
                )
            );
        $reader->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('stub_reader'));

        $processor = $this->getMock('Akeneo\\Bundle\\BatchBundle\\Tests\\Unit\\Item\\ItemProcessorTestHelper');
        $processor->expects($this->never())
            ->method('process');

        $writer = $this->getMock('Akeneo\\Bundle\\BatchBundle\\Tests\\Unit\\Item\\ItemWriterTestHelper');
        $writer->expects($this->never())
            ->method('write');

        $this->itemStep->setReader($reader);
        $this->itemStep->setProcessor($processor);
        $this->itemStep->setWriter($writer);

        $stepExecution->expects($this->once())
            ->method('addWarning')
            ->with(
                'stub_reader',
                'The read item is invalid',
                array(),
                array('foo' => 'bar')
            );

        $this->itemStep->setBatchSize(5);
        $this->itemStep->execute($stepExecution);
    }

    public function testDispatchProcessInvalidItemException()
    {
        $stepExecution = $this->getMockBuilder('Akeneo\\Bundle\\BatchBundle\\Entity\\StepExecution')
            ->disableOriginalConstructor()
            ->getMock();
        $stepExecution->expects($this->any())
            ->method('getStatus')
            ->will($this->returnValue(new BatchStatus(BatchStatus::STARTING)));
        $stepExecution->expects($this->any())
            ->method('getExitStatus')
            ->will($this->returnValue(new BatchStatus(BatchStatus::STARTED)));

        $this->eventDispatcher
            ->expects($this->at(1))
            ->method('dispatch')
            ->with(
                EventInterface::INVALID_ITEM,
                $this->logicalAnd(
                    $this->isInstanceOf('Akeneo\\Component\\Batch\\Event\\InvalidItemEvent'),
                    $this->attributeEqualTo('reason', 'The processed item is invalid'),
                    $this->attributeEqualTo('item', array('foo' => 'bar'))
                )
            );

        $reader = $this->getMock('Akeneo\\Bundle\\BatchBundle\\Tests\\Unit\\Item\\ItemReaderTestHelper');
        $reader->expects($this->exactly(2))
            ->method('read')
            ->will(
                $this->onConsecutiveCalls(
                    $this->returnValue(array('foo' => 'bar')),
                    $this->returnValue(null)
                )
            );

        $processor = $this->getMock('Akeneo\\Bundle\\BatchBundle\\Tests\\Unit\\Item\\ItemProcessorTestHelper');
        $processor->expects($this->exactly(1))
            ->method('process')
            ->will(
                $this->throwException(
                    new InvalidItemException('The processed item is invalid', array('foo' => 'bar'))
                )
            );
        $processor->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('stub_processor'));

        $writer = $this->getMock('Akeneo\\Bundle\\BatchBundle\\Tests\\Unit\\Item\\ItemWriterTestHelper');
        $writer->expects($this->never())
            ->method('write');

        $this->itemStep->setReader($reader);
        $this->itemStep->setProcessor($processor);
        $this->itemStep->setWriter($writer);

        $stepExecution->expects($this->once())
            ->method('addWarning')
            ->with(
                'stub_processor',
                'The processed item is invalid',
                array(),
                array('foo' => 'bar')
            );

        $this->itemStep->setBatchSize(5);
        $this->itemStep->execute($stepExecution);
    }

    public function testDispatchWriteInvalidItemException()
    {
        $stepExecution = $this->getMockBuilder('Akeneo\\Bundle\\BatchBundle\\Entity\\StepExecution')
            ->disableOriginalConstructor()
            ->getMock();
        $stepExecution->expects($this->any())
            ->method('getStatus')
            ->will($this->returnValue(new BatchStatus(BatchStatus::STARTING)));
        $stepExecution->expects($this->any())
            ->method('getExitStatus')
            ->will($this->returnValue(new BatchStatus(BatchStatus::STARTED)));

        $this->eventDispatcher
            ->expects($this->at(1))
            ->method('dispatch')
            ->with(
                EventInterface::INVALID_ITEM,
                $this->logicalAnd(
                    $this->isInstanceOf('Akeneo\\Component\\Batch\\Event\\InvalidItemEvent'),
                    $this->attributeEqualTo('reason', 'The written item is invalid'),
                    $this->attributeEqualTo('item', array('foo' => 'bar'))
                )
            );

        $reader = $this->getMock('Akeneo\\Bundle\\BatchBundle\\Tests\\Unit\\Step\\Stub\\ReaderStub');
        $reader->expects($this->exactly(2))
            ->method('read')
            ->will(
                $this->onConsecutiveCalls(
                    $this->returnValue(array('foo' => 'bar')),
                    $this->returnValue(null)
                )
            );

        $processor = $this->getMock('Akeneo\\Bundle\\BatchBundle\\Tests\\Unit\\Item\\ItemProcessorTestHelper');
        $processor->expects($this->exactly(1))
            ->method('process')
            ->will(
                $this->returnValue(array('foo' => 'bar'))
            );

        $writer = $this->getMock('Akeneo\\Bundle\\BatchBundle\\Tests\\Unit\\Item\\ItemWriterTestHelper');
        $writer->expects($this->exactly(1))
            ->method('write')
            ->will(
                $this->throwException(
                    new InvalidItemException('The written item is invalid', array('foo' => 'bar'))
                )
            );
        $writer->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('stub_writer'));

        $this->itemStep->setReader($reader);
        $this->itemStep->setProcessor($processor);
        $this->itemStep->setWriter($writer);

        $stepExecution->expects($this->once())
            ->method('addWarning')
            ->with(
                'stub_writer',
                'The written item is invalid',
                array(),
                array('foo' => 'bar')
            );

        $this->itemStep->setBatchSize(5);
        $this->itemStep->execute($stepExecution);
    }

    /**
     * Assert the entity tested
     *
     * @param object $entity
     */
    protected function assertEntity($entity)
    {
        $this->assertInstanceOf('Akeneo\\Component\\Batch\\Step\\ItemStep', $entity);
    }

    private function getReaderMock(array $configuration, array $fields = array())
    {
        $reader = $this
            ->getMockBuilder('Akeneo\\Bundle\\BatchBundle\\Tests\\Unit\\Item\\ItemReaderTestHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $reader->expects($this->any())
            ->method('getConfiguration')
            ->will($this->returnValue($configuration));

        $reader->expects($this->any())
            ->method('getConfigurationFields')
            ->will($this->returnValue($fields));

        return $reader;
    }

    private function getProcessorMock(array $configuration, array $fields = array())
    {
        $processor = $this
            ->getMockBuilder('Akeneo\\Bundle\\BatchBundle\\Tests\\Unit\\Item\\ItemProcessorTestHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $processor->expects($this->any())
            ->method('getConfiguration')
            ->will($this->returnValue($configuration));

        $processor->expects($this->any())
            ->method('getConfigurationFields')
            ->will($this->returnValue($fields));

        return $processor;
    }

    private function getWriterMock(array $configuration, array $fields = array())
    {
        $writer = $this
            ->getMockBuilder('Akeneo\\Bundle\\BatchBundle\\Tests\\Unit\\Item\\ItemWriterTestHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $writer->expects($this->any())
            ->method('getConfiguration')
            ->will($this->returnValue($configuration));

        $writer->expects($this->any())
            ->method('getConfigurationFields')
            ->will($this->returnValue($fields));

        return $writer;
    }
}
