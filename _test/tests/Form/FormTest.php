<?php

namespace dokuwiki\test\Form;

use dokuwiki\Form;
use DOMWrap\Document;
use dokuwiki\test\Form\Form as TestForm;

class FormTest extends \DokuWikiTest
{

    /**
     * checks that an empty form is initialized correctly
     */
    function testDefaults()
    {
        global $INPUT;
        global $ID;
        $ID = 'some:test';
        $INPUT->get->set('id', $ID);
        $INPUT->get->set('foo', 'bar');

        $form = new Form\Form();
        $html = $form->toHTML();
        $pq = (new Document())->html($html);

        $this->assertTrue($pq->find('form')->hasClass('doku_form'));
        $this->assertEquals(wl($ID, array('foo' => 'bar'), false, '&'), $pq->find('form')->attr('action'));
        $this->assertEquals('post', $pq->find('form')->attr('method'));

        $this->assertTrue($pq->find('input[name=sectok]')->count() == 1);
    }

    function testFieldsetBalance()
    {
        $form = new TestForm();
        $form->addFieldsetOpen();
        $form->addHTML('ignored');
        $form->addFieldsetClose();
        $form->balanceFieldsets();

        $this->assertEquals(
            array(
                'fieldsetopen',
                'html',
                'fieldsetclose',
            ),
            $form->getElementTypeList()
        );

        $form = new TestForm();
        $form->addHTML('ignored');
        $form->addFieldsetClose();
        $form->balanceFieldsets();

        $this->assertEquals(
            array(
                'fieldsetopen',
                'html',
                'fieldsetclose',
            ),
            $form->getElementTypeList()
        );

        $form = new TestForm();
        $form->addFieldsetOpen();
        $form->addHTML('ignored');
        $form->balanceFieldsets();

        $this->assertEquals(
            array(
                'fieldsetopen',
                'html',
                'fieldsetclose',
            ),
            $form->getElementTypeList()
        );

        $form = new TestForm();
        $form->addHTML('ignored');
        $form->addFieldsetClose();
        $form->addHTML('ignored');
        $form->addFieldsetOpen();
        $form->addHTML('ignored');
        $form->balanceFieldsets();

        $this->assertEquals(
            array(
                'fieldsetopen',
                'html',
                'fieldsetclose',
                'html',
                'fieldsetopen',
                'html',
                'fieldsetclose',
            ),
            $form->getElementTypeList()
        );
    }
}
