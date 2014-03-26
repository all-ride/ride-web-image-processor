<?php

namespace ride\web\base\controller;

use ride\library\http\Response;
use ride\library\image\Dimension;
use ride\library\image\io\ImageFactory;
use ride\library\image\Point;
use ride\library\system\file\browser\FileBrowser;
use ride\library\validation\exception\ValidationException;

/**
 * Controller to process the size of an image
 */
class ImageController extends AbstractController {

    /**
     * Processes the provided image
     * @return null
     */
    public function processAction(FileBrowser $fileBrowser, ImageFactory $imageFactory) {
        $file = null;
        $path = $this->request->getQueryParameter('img');

        if ($path) {
            $file = $fileBrowser->getFile($path);

            if (!$file) {
                $file = $fileBrowser->getPublicFile($path);
            }
        }

        if (!$file) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $referer = $this->request->getQueryParameter('referer');

        $form = $this->createFormBuilder();
        $form->addRow('x1', 'hidden', array(
            'type' => 'hidden',
        ));
        $form->addRow('y1', 'hidden', array(
            'type' => 'hidden',
        ));
        $form->addRow('x2', 'hidden', array(
            'type' => 'hidden',
        ));
        $form->addRow('y2', 'hidden', array(
            'type' => 'hidden',
        ));
        $form->setRequest($this->request);

        $form = $form->build();
        if($form->isSubmitted()) {
            try {
                $data = $form->getData();

                if ($data['x2'] && $data['y2']) {
                    $dimension = new Dimension($data['x2'] - $data['x1'], $data['y2'] - $data['y1']);
                    $start = new Point($data['x1'], $data['y1']);

                    $image = $imageFactory->read($file);
                    $image = $image->crop($dimension, $start);

                    $imageFactory->write($file, $image);
                }

                if (!$referer) {
                    $referer = $this->request->getBaseUrl();
                }

                $this->response->setRedirect($referer);

                return;
            } catch(ValidationException $exception) {
                $this->setValidationException($exception, $form);
            }
        }

        $this->setTemplateView('base/image.processor', array(
            'form' => $form->getView(),
            'path' => $path,
            'referer' => $referer,
        ));
    }

}