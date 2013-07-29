<?php
/**
 * Article module upload controller
 *
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @copyright       Copyright (c) Engine http://www.xoopsengine.org
 * @license         http://www.xoopsengine.org/license New BSD License
 * @author          Lijun Dong <lijun@eefocus.com>
 * @author          Zongshu Lin <zongshu@eefocus.com>
 * @since           1.0
 * @package         Module\Article
 */

namespace Module\Article\Controller\Front;

use Pi\Mvc\Controller\ActionController;
use Pi;
use Module\Article\Upload;
use Module\Article\Image;
use Module\Article\Service;
use Module\Article\Model\Asset;
use Zend\File\Transfer\Transfer;
use Pi\File\Transfer\Upload as XoopsUpload;

class UploadController extends ActionController
{
    public function indexAction()
    {
        Pi::service('log')->active(false);
        exit();
    }

    public function authorPhotoAction()
    {
        Pi::service('log')->active(false);
        $module = $this->getModule();

        $result = false;
        $data   = $return = array();
        $id     = Service::getParam($this, 'id', 0);
        if (empty($id)) {
            $id = Service::getParam($this, 'fake_id', 0);
        }

        if ($id) {
//            $uploadInfo = $this->request->getFiles('photo');
//            $fileName   = Upload::getAssetFileName($uploadInfo['name'], Asset::FIELD_TYPE_IMAGE, $module);
//            $pathInfo   = pathinfo($fileName);
//            $filters    = array(
//                array('filter' => 'Pi\Filter\File\Rename', array('target' => $pathInfo['basename'], 'overwrite' => true)),
//            );
//
//            $validators = array(
//                array('Extension', false, $this->config('image_extension')),
//                array('Size', false, $this->config('max_image_size')),
//            );
//
//            $transfer   = new Transfer();
//            $transfer->setDestination(Pi::path($pathInfo['dirname']));
//            $transfer->addValidators($validators);
//            $transfer->addFilters($filters);

            $rawInfo = $this->request->getFiles('upload');
            $rename  = $id;

            $destination = Upload::getTargetDir('author', $module, true, false);
            $ext         = pathinfo($rawInfo['name'], PATHINFO_EXTENSION);
            if ($ext) {
                $rename .= '.' . $ext;
            }

            $upload = new XoopsUpload();
            $upload->setDestination(Pi::path($destination))
                   ->setRename($rename)
                   ->setExtension($this->config('image_extension'))
                   ->setSize($this->config('max_image_size'));

            if ($upload->isValid()) {
                $upload->receive();

                $fileName = $destination . '/' . $rename;

                // Scale image
                $uploadInfo['tmp_name'] = $fileName;
                $uploadInfo['w']        = $this->config('author_width');
                $uploadInfo['h']        = $this->config('author_height');

                Upload::saveAuthorPhoto($uploadInfo);

                // Save photo to author
                $rowAuthor = $this->getModel('author')->find($id);
                if ($rowAuthor) {
                    if ($rowAuthor->photo && $rowAuthor->photo != $fileName) {
                        unlink(Pi::path($rowAuthor->photo));
                    }

                    $rowAuthor->photo = $fileName;
                    $rowAuthor->save();
                } else {
                    // Or save info to session
                    $session = Upload::getUploadSession($module, 'author');
                    $session->$id = $uploadInfo;
                }

                $result = true;

                $imageSize = getimagesize(Pi::path($fileName));

                // Prepare return data
                $return['data'] = array(
                    'originalName' => $rawInfo['name'],
                    'size'         => $rawInfo['size'],
                    'w'            => $imageSize['0'],
                    'h'            => $imageSize['1'],
                    'preview_url'  => Pi::url($fileName),
                );
            } else {
                $return['message'] = $upload->getMessages();
            }
        } else {
            $return['message'] = __('Invalid id');
        }

        $return['status'] = $result ? AjaxController::AJAX_RESULT_TRUE : AjaxController::AJAX_RESULT_FALSE;

        echo json_encode($return);

        exit();
    }

    public function categoryImageAction()
    {
        Pi::service('log')->active(false);
        $module = $this->getModule();

        $result = false;
        $data   = $return = array();
        $id     = Service::getParam($this, 'id', 0);
        if (empty($id)) {
            $id = Service::getParam($this, 'fake_id', 0);
        }

        if ($id) {
//            $uploadInfo = $this->request->getFiles('image');
//            $fileName   = Upload::getAssetFileName($uploadInfo['name'], Asset::FIELD_TYPE_IMAGE, $module);
//            $pathInfo   = pathinfo($fileName);
//            $filters    = array(
//                array('filter' => 'Pi\Filter\File\Rename', array('target' => $pathInfo['basename'], 'overwrite' => true)),
//            );
//
//            $validators = array(
//                array('Extension', false, $this->config('image_extension')),
//                array('Size', false, $this->config('max_image_size')),
//            );
//
//            $transfer   = new Transfer();
//            $transfer->setDestination(Pi::path($pathInfo['dirname']));
//            $transfer->addValidators($validators);
//            $transfer->addFilters($filters);

            $rawInfo = $this->request->getFiles('upload');
            $rename  = $id;

            $destination = Upload::getTargetDir('category', $module, true, false);
            $ext         = pathinfo($rawInfo['name'], PATHINFO_EXTENSION);
            if ($ext) {
                $rename .= '.' . $ext;
            }

            $upload = new XoopsUpload();
            $upload->setDestination(Pi::path($destination))
                   ->setRename($rename)
                   ->setExtension($this->config('image_extension'))
                   ->setSize($this->config('max_image_size'));

            if ($upload->isValid()) {
                $upload->receive();

                $fileName = $destination . '/' . $rename;

                // Scale image
                $uploadInfo['tmp_name'] = $fileName;
                $uploadInfo['w']        = $this->config('category_width');
                $uploadInfo['h']        = $this->config('category_height');

                Upload::saveCategoryImage($uploadInfo);

                // Save image to category
                $rowCategory = $this->getModel('category')->find($id);
                if ($rowCategory) {
                    if ($rowCategory->image && $rowCategory->image != $fileName) {
                        unlink(Pi::path($rowCategory->image));
                    }

                    $rowCategory->image = $fileName;
                    $rowCategory->save();
                } else {
                    // Or save info to session
                    $session = Upload::getUploadSession($module, 'category');
                    $session->$id = $uploadInfo;
                }

                $result = true;

                $imageSize = getimagesize(Pi::path($fileName));

                // Prepare return data
                $return['data'] = array(
                    'originalName' => $rawInfo['name'],
                    'size'         => $rawInfo['size'],
                    'w'            => $imageSize['0'],
                    'h'            => $imageSize['1'],
                    'preview_url'  => Pi::url($fileName),
                );
            } else {
                $return['message'] = $upload->getMessages();
            }
        } else {
            $return['message'] = __('Invalid id');
        }

        $return['status'] = $result ? AjaxController::AJAX_RESULT_TRUE : AjaxController::AJAX_RESULT_FALSE;

        echo json_encode($return);

        exit();
    }

    public function featureImageAction()
    {
        Pi::service('log')->active(false);
        $module = $this->getModule();

        $result = false;
        $data   = $return = array();
        $id     = Service::getParam($this, 'id', 0);
        if (empty($id)) {
            $id = Service::getParam($this, 'fake_id', 0);
        }

        if ($id) {
//            $uploadInfo = $this->request->getFiles('featureImage');
//            $fileName   = Upload::getAssetFileName($uploadInfo['name'], Asset::FIELD_TYPE_IMAGE, $module);
//            $pathInfo   = pathinfo($fileName);
//            $filters    = array(
//                array('filter' => 'Pi\Filter\File\Rename', array('target' => $pathInfo['basename'], 'overwrite' => true)),
//            );
//
//            $validators = array(
//                array('Extension', false, $this->config('image_extension')),
//                array('Size', false, $this->config('max_image_size')),
//            );
//
//            $transfer   = new Transfer();
//            $transfer->setDestination(Pi::path($pathInfo['dirname']));
//            $transfer->addValidators($validators);
//            $transfer->addFilters($filters);

            $destination = Upload::getTargetDir('feature', $module, true);
            $upload = new XoopsUpload();
            $upload->setDestination(Pi::path($destination))
                   ->setRename()
                   ->setExtension($this->config('image_extension'))
                   ->setSize($this->config('max_image_size'));

            if ($upload->isValid()) {
                $upload->receive();

                $baseName = $upload->getUploaded('featureImage', false);
                if (is_array($baseName)) {
                    $baseName = current($baseName);
                }
                $fileName = $destination . '/' . $baseName;

                $rawInfo = $this->request->getFiles('featureImage');

                // Scale image
                $uploadInfo['tmp_name'] = $fileName;
                $uploadInfo['w']        = $this->config('feature_width');
                $uploadInfo['h']        = $this->config('feature_height');
                $uploadInfo['thumb_w']  = $this->config('feature_thumb_width');
                $uploadInfo['thumb_h']  = $this->config('feature_thumb_height');

                Upload::saveFeatureImage($uploadInfo);

                // Save image to draft
                $rowDraft = $this->getModel('draft')->find($id);
                if ($rowDraft) {
                    $rowDraft->image = $fileName;
                    $rowDraft->save();
                } else {
                    // Or save info to session
                    $session = Upload::getUploadSession($module);
                    $session->$id = $uploadInfo;
                }

                $result = true;

                $imageSize = getimagesize(Pi::path($fileName));

                // Prepare return data
                $return['data'] = array(
                    'originalName' => $rawInfo['name'],
                    'size'         => $rawInfo['size'],
                    'w'            => $imageSize['0'],
                    'h'            => $imageSize['1'],
                    'preview_url'  => Pi::url(Upload::getThumbFromOriginal($fileName)),
                );
            } else {
                $return['message'] = $upload->getMessages();
            }
        } else {
            $return['message'] = __('Invalid id');
        }

        $return['status'] = $result ? AjaxController::AJAX_RESULT_TRUE : AjaxController::AJAX_RESULT_FALSE;

        echo json_encode($return);

        exit();
    }

    public function attachmentAction()
    {
        Pi::service('log')->active(false);
        $module = $this->getModule();

        $result = false;
        $data   = $return = array();
        $id     = Service::getParam($this, 'id', 0);
        if (empty($id)) {
            $id = Service::getParam($this, 'fake_id', 0);
        }

        if ($id) {
//            $uploadInfo = $this->request->getFiles('attachment');
//            $fileName   = Upload::getAssetFileName($uploadInfo['name'], Asset::FIELD_TYPE_ATTACHMENT, $module);
//            $pathInfo   = pathinfo($fileName);
//            $filters    = array(
//                array('filter' => 'Pi\Filter\File\Rename', array('target' => $pathInfo['basename'], 'overwrite' => true)),
//            );
//
//            $validators = array(
//                array('Extension', false, $this->config('attachment_extension')),
//                array('Size', false, $this->config('max_attachment_size')),
//            );
//
//            $transfer   = new Transfer();
//            $transfer->setDestination(Pi::path($pathInfo['dirname']));
//            $transfer->addValidators($validators);
//            $transfer->addFilters($filters);

            $destination = Upload::getTargetDir(Asset::FIELD_TYPE_ATTACHMENT, $module, true);
            $upload = new XoopsUpload();
            $upload->setDestination(Pi::path($destination))
                ->setRename()
                ->setExtension($this->config('attachment_extension'))
                ->setSize($this->config('max_attachment_size'));

            if ($upload->isValid()) {
                $upload->receive();

                $baseName = $upload->getUploaded('attachment', false);
                if (is_array($baseName)) {
                    $baseName = current($baseName);
                }
                $fileName = $destination . '/' . $baseName;
                $pathInfo = pathinfo($fileName);

                $rawInfo = $this->request->getFiles('attachment');

                // Save info to DB
                $data = array(
                    'original_name' => $rawInfo['name'],
                    'name'          => $pathInfo['filename'],
                    'extension'     => $pathInfo['extension'],
                    'size'          => $rawInfo['size'],
                    'mime_type'     => $rawInfo['type'],
                    'path'          => $fileName,
                    'time_create'   => time(),
                    'user'          => Pi::registry('user')->id,
                    'draft'         => $id,
                    'type'          => Asset::FIELD_TYPE_ATTACHMENT,
                    'published'     => 0,
                );

                $rowDraftAsset = $this->getModel('draft_asset')->createRow($data);
                $rowDraftAsset->save();

                if ($rowDraftAsset->id) {
                    $result = true;

                    // Prepare return data
                    $return['data'] = array(
                        'id'           => $rowDraftAsset->id,
                        'originalName' => $rawInfo['name'],
                        'size'         => $rawInfo['size'],
                        'delete_url'   => $this->url(
                            '',
                            array(
                                'controller' => 'ajax',
                                'action'     => 'remove.asset',
                                'id'         => $rowDraftAsset->id,
                            )
                        ),
                        'download_url' => $this->url(
                            'default',
                            array(
                                'controller' => 'download',
                                'action'     => 'attachment',
                                'name'       => $data['name'],
                            )
                        ),
                    );
                } else {
                    $return['message'] = __('Failed to save database');
                }
            } else {
                $return['message'] = $upload->getMessages();
            }
        } else {
            $return['message'] = __('Invalid id');
        }

        $return['status'] = $result ? AjaxController::AJAX_RESULT_TRUE : AjaxController::AJAX_RESULT_FALSE;

        echo json_encode($return);

        exit();
    }

    protected function imageAction()
    {
        Pi::service('log')->active(false);
        $module = $this->getModule();

        $result = false;
        $data   = $return = array();
        $id     = Service::getParam($this, 'id', 0);
        if (empty($id)) {
            $id = Service::getParam($this, 'fake_id', 0);
        }

        if ($id) {
//            $uploadInfo = $this->request->getFiles('image');
//            $fileName   = Upload::getAssetFileName($uploadInfo['name'], Asset::FIELD_TYPE_IMAGE, $module);
//            $pathInfo   = pathinfo($fileName);
//            $filters    = array(
//                array('filter' => 'Pi\Filter\File\Rename', array('target' => $pathInfo['basename'], 'overwrite' => true)),
//            );
//
//            $validators = array(
//                array('Extension', false, $this->config('image_extension')),
//                array('Size', false, $this->config('max_image_size')),
//            );
//
//            $transfer   = new Transfer();
//            $transfer->setDestination(Pi::path($pathInfo['dirname']));
//            $transfer->addValidators($validators);
//            $transfer->addFilters($filters);

            $destination = Upload::getTargetDir(Asset::FIELD_TYPE_IMAGE, $module, true);
            $upload = new XoopsUpload();
            $upload->setDestination(Pi::path($destination))
                ->setRename()
                ->setExtension($this->config('image_extension'))
                ->setSize($this->config('max_image_size'));

            if ($upload->isValid()) {
                $upload->receive();

                $baseName = $upload->getUploaded('image', false);
                if (is_array($baseName)) {
                    $baseName = current($baseName);
                }
                $fileName = $destination . '/' . $baseName;
                $pathInfo = pathinfo($fileName);

                $rawInfo = $this->request->getFiles('image');

                // Resize image
                $uploadInfo['tmp_name'] = $fileName;
                $uploadInfo['thumb_w']  = $this->config('content_thumb_w');
                $uploadInfo['thumb_h']  = $this->config('content_thumb_h');

                $thumbName = Upload::createThumb($uploadInfo);

                // Save info to DB
                $data = array(
                    'original_name' => $rawInfo['name'],
                    'name'          => $pathInfo['filename'],
                    'extension'     => $pathInfo['extension'],
                    'size'          => $rawInfo['size'],
                    'mime_type'     => $rawInfo['type'],
                    'path'          => $fileName,
                    'time_create'   => time(),
                    'user'          => Pi::registry('user')->id,
                    'draft'         => $id,
                    'type'          => Asset::FIELD_TYPE_IMAGE,
                    'published'     => 0,
                );

                $rowDraftAsset = $this->getModel('draft_asset')->createRow($data);
                $rowDraftAsset->save();

                if ($rowDraftAsset->id) {
                    $result = true;

                    $imageSize = getimagesize(Pi::path($fileName));

                    // Prepare return data
                    $return['data'] = array(
                        'id'           => $rowDraftAsset->id,
                        'originalName' => $rawInfo['name'],
                        'size'         => $rawInfo['size'],
                        'w'            => $imageSize['0'],
                        'h'            => $imageSize['1'],
                        'delete_url'   => $this->url(
                            '',
                            array(
                                'controller' => 'ajax',
                                'action'     => 'remove.asset',
                                'id'         => $rowDraftAsset->id,
                            )
                        ),
                        'preview_url' => Pi::url($fileName),
                        'thumb_url'   => Pi::url($thumbName),
                    );
                } else {
                    $return['message'] = __('Failed to save database');
                }
            } else {
                $return['message'] = $upload->getMessages();
            }
        } else {
            $return['message'] = __('Invalid id');
        }

        $return['status'] = $result ? AjaxController::AJAX_RESULT_TRUE : AjaxController::AJAX_RESULT_FALSE;

        echo json_encode($return);

        exit();
    }
}
