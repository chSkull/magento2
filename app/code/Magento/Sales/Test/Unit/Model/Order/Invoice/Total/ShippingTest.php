<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Magento\Sales\Test\Unit\Model\Order\Invoice\Total;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ShippingTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Retrieve new invoice collection from an array of invoices' data
     *
     * @param array $invoicesData
     * @return \Magento\Framework\Data\Collection
     */
    protected function _getInvoiceCollection(array $invoicesData)
    {
        $className = \Magento\Sales\Model\Order\Invoice::class;
        $result = new \Magento\Framework\Data\Collection(
            $this->getMock(\Magento\Framework\Data\Collection\EntityFactory::class, [], [], '', false)
        );
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $arguments = [
            'orderFactory' => $this->getMock(\Magento\Sales\Model\OrderFactory::class, [], [], '', false),
            'orderResourceFactory' => $this->getMock(
                \Magento\Sales\Model\ResourceModel\OrderFactory::class,
                [],
                [],
                '',
                false
            ),
            'calculatorFactory' => $this->getMock(
                \Magento\Framework\Math\CalculatorFactory::class,
                    [],
                    [],
                    '',
                    false
                ),
            'invoiceItemCollectionFactory' => $this->getMock(
                \Magento\Sales\Model\ResourceModel\Order\Invoice\Item\CollectionFactory::class,
                [],
                [],
                '',
                false
            ),
            'invoiceCommentFactory' => $this->getMock(
                \Magento\Sales\Model\Order\Invoice\CommentFactory::class,
                [],
                [],
                '',
                false
            ),
            'commentCollectionFactory' => $this->getMock(
                \Magento\Sales\Model\ResourceModel\Order\Invoice\Comment\CollectionFactory::class,
                [],
                [],
                '',
                false
            ),
        ];
        foreach ($invoicesData as $oneInvoiceData) {
            $arguments['data'] = $oneInvoiceData;
            $arguments = $objectManagerHelper->getConstructArguments($className, $arguments);
            /** @var $prevInvoice \Magento\Sales\Model\Order\Invoice */
            $prevInvoice = $this->getMock($className, ['_init'], $arguments);
            $result->addItem($prevInvoice);
        }
        return $result;
    }

    /**
     * @dataProvider collectDataProvider
     * @param array $prevInvoicesData
     * @param float $orderShipping
     * @param float $invoiceShipping
     * @param float $expectedShipping
     */
    public function testCollect(array $prevInvoicesData, $orderShipping, $invoiceShipping, $expectedShipping)
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $arguments = [
            'productFactory' => $this->getMock(\Magento\Catalog\Model\ProductFactory::class, [], [], '', false),
            'orderItemCollectionFactory' => $this->getMock(
                \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory::class,
                [],
                [],
                '',
                false
            ),
            'serviceOrderFactory' => $this->getMock(
                \Magento\Sales\Model\Service\OrderFactory::class,
                [],
                [],
                '',
                false
            ),
            'currencyFactory' => $this->getMock(
                \Magento\Directory\Model\CurrencyFactory::class,
                [],
                [],
                '',
                false
            ),
            'orderHistoryFactory' => $this->getMock(
                \Magento\Sales\Model\Order\Status\HistoryFactory::class,
                [],
                [],
                '',
                false
            ),
            'orderTaxCollectionFactory' => $this->getMock(
                \Magento\Tax\Model\ResourceModel\Sales\Order\Tax\CollectionFactory::class,
                [],
                [],
                '',
                false
            ),
        ];
        $orderConstructorArgs = $objectManager->getConstructArguments(\Magento\Sales\Model\Order::class, $arguments);
        /** @var $order \Magento\Sales\Model\Order|PHPUnit_Framework_MockObject_MockObject */
        $order = $this->getMock(
            \Magento\Sales\Model\Order::class,
            ['_init', 'getInvoiceCollection', '__wakeup'],
            $orderConstructorArgs,
            '',
            false
        );
        $order->setData('shipping_amount', $orderShipping);
        $order->expects(
            $this->any()
        )->method(
            'getInvoiceCollection'
        )->will(
            $this->returnValue($this->_getInvoiceCollection($prevInvoicesData))
        );
        /** @var $invoice \Magento\Sales\Model\Order\Invoice|PHPUnit_Framework_MockObject_MockObject */
        $invoice = $this->getMock(\Magento\Sales\Model\Order\Invoice::class, ['_init', '__wakeup'], [], '', false);
        $invoice->setData('shipping_amount', $invoiceShipping);
        $invoice->setOrder($order);

        $total = new \Magento\Sales\Model\Order\Invoice\Total\Shipping();
        $total->collect($invoice);

        $this->assertEquals($expectedShipping, $invoice->getShippingAmount());
    }

    public static function collectDataProvider()
    {
        return [
            'no previous invoices' => [
                'prevInvoicesData' => [[]],
                'orderShipping' => 10.00,
                'invoiceShipping' => 5.00,
                'expectedShipping' => 10.00,
            ],
            'zero shipping in previous invoices' => [
                'prevInvoicesData' => [['shipping_amount' => '0.0000']],
                'orderShipping' => 10.00,
                'invoiceShipping' => 5.00,
                'expectedShipping' => 10.00,
            ],
            'non-zero shipping in previous invoices' => [
                'prevInvoicesData' => [['shipping_amount' => '10.000']],
                'orderShipping' => 10.00,
                'invoiceShipping' => 5.00,
                'expectedShipping' => 0,
            ]
        ];
    }
}
