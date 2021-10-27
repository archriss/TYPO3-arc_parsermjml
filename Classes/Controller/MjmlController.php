<?php

namespace Archriss\ArcParsermjml\Controller;

use Archriss\ArcParsermjml\Domain\Model\Dto\FormDto;
use GuzzleHttp\Client;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Extbase\Validation\Validator\GenericObjectValidator;

class MjmlController extends ActionController
{

    /**
     * Default API uri
     */
    protected const baseUri = 'https://api.mjml.io/v1/';

    /**
     * @var array 
     */
    protected $conf = [];

    /**
     * BackendTemplateView Container
     *
     * @var string
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * Resource Factory instance
     * @var \TYPO3\CMS\Core\Resource\ResourceFactory
     */
    protected $resourceFactory;

    /**
     * @var \TYPO3\CMS\Core\Resource\Folder
     */
    protected $folder;

    /**
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * @var \TYPO3\CMS\Core\Service\MarkerBasedTemplateService
     */
    protected $markerService;

    /**
     * @param \TYPO3\CMS\Core\Resource\ResourceFactory $resourceFactory
     */
    public function injectResourceFactory(ResourceFactory $resourceFactory)
    {
        $this->resourceFactory = $resourceFactory;
    }

    /**
     * @param \TYPO3\CMS\Core\Service\MarkerBasedTemplateService $markerBasedTemplateService
     */
    public function injectMarkerService(MarkerBasedTemplateService $markerBasedTemplateService)
    {
        $this->markerService = $markerBasedTemplateService;
    }

    /**
     * Initialize Folder
     */
    public function initializeAction()
    {
        // Prepare filter
        $filterObject = GeneralUtility::makeInstance(FileExtensionFilter::class);
        $filterObject->setAllowedFileExtensions('mjml');
        // Get current folder
        /* @var $folder \TYPO3\CMS\Core\Resource\Folder */
        $this->folder = $this->resourceFactory->getFolderObjectFromCombinedIdentifier(GeneralUtility::_GP('id'));
        $this->folder->getStorage()->addFileAndFolderNameFilter([$filterObject, 'filterFileList']);
        // Extension configuration
        $conf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('arc_parsermjml');
        $this->conf = $conf;
    }

    /**
     * @param bool $success
     * @param array $errorDatas
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     */
    public function configureAction($success = null, $errorDatas = null)
    {
        // Generic variable and definition
        $files = [];
        $state = -2;
        $showForm = false;

        // Test case
        if ($this->conf['auth']['appId'] === '' || $this->conf['auth']['publicKey'] === '' || $this->conf['auth']['secretKey'] === '') {
            $key = 'error.apiNotSet';
            $state = 2;
        } else {
            if ($this->folder->getIdentifier() == '/') {
                $key = 'error.rootFolder';
                $state = 1;
            } else {
                if ($this->folder->getFileCount() > 0) {
                    if (!is_null($success)) {
                        if (!$success) {
                            $key = 'error.api';
                            $state = 1;
                        } else {
                            $key = 'success.api';
                            $state = 0;
                        }
                    } else {
                        $key = 'info.files';
                        $files = $this->folder->getFiles();
                        $showForm = true;
                    }
                } else {
                    $key = 'error.noMjmlFiles';
                    $state = 2;
                }
            }
        }

        // Rendering
        $this->view->getModuleTemplate()->getDocHeaderComponent()->setMetaInformation([
            '_additional_info' => '',
            'combined_identifier' => $this->folder->getCombinedIdentifier(),
        ]);
        $this->view->assignMultiple([
            'infoboxTitle' => $key,
            'infoboxState' => $state,
            'files' => $files,
            'showForm' => $showForm,
            'success' => $success,
            'errorDatas' => $errorDatas,
        ]);
    }

    public function initializeConvertAction()
    {
        /* @var $propertyValidator \TYPO3\CMS\Extbase\Validation\Validator\GenericObjectValidator */
        $propertyValidator = $this->validatorResolver->createValidator(GenericObjectValidator::class);
        $propertyValidator->addPropertyValidator(
            'style',
            $this->validatorResolver->createValidator('NotEmpty')
        );

        /* @var $dto \TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface */
        $dto = $this->arguments->getArgument('dto')->getValidator();
        $dto->addValidator($propertyValidator);

        // Initialize mjml configuration
        if ($this->conf['auth']['baseUri'] === '') {
            $this->conf['auth']['baseUri'] = self::baseUri;
        }
        $this->client = GeneralUtility::makeInstance(
            Client::class,
            ['base_uri' => $this->conf['auth']['baseUri']]
        );
    }

    /**
     * @param \Archriss\ArcParsermjml\Domain\Model\Dto\FormDto $dto
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    public function convertAction(FormDto $dto)
    {
        $success = true;
        $errorDatas = [];

        // Selected style file
        $styleFile = $this->resourceFactory->getFileObjectByStorageAndIdentifier(
            $this->folder->getStorage()->getUid(),
            $dto->getStyle()
        );

        DebuggerUtility::var_dump($this->folder->getFiles(), $this->folder->getFileCount());
        foreach ($this->folder->getFiles() as $file) {
            $destFilename = $file->getNameWithoutExtension() . '.html';
            DebuggerUtility::var_dump($destFilename, $file->getIdentifier());
            // Only process for non existing files or if override is set
            if (
                $this->folder->hasFile($destFilename) === false ||
                ($dto->isOverride() && $this->folder->hasFile($destFilename))
            ) {
                // Handle only common files
                if ($file->getIdentifier() !== $styleFile->getIdentifier()) {
                    // Get the mjml file
                    $response = $this->client->post('render', [
                        'auth' => [$this->conf['auth']['appId'], $this->conf['auth']['secretKey']],
                        'body' => \GuzzleHttp\json_encode([
                            'mjml' => $this->getMjmlFor($file, $styleFile)
                        ])
                    ]);

                    // Parse response 
                    $data = \GuzzleHttp\json_decode($response->getBody()->getContents(), true);
                    DebuggerUtility::var_dump($data['errors'], $data === false ? 'null' : 'ok');
                    if (false === $data || count($data['errors'])) {
                        $success = false;
                        $errorDatas[$file->getIdentifier()] = $data['errors'];
                    } else {
                        $this->folder->createFile($destFilename)->setContents(
                            $this->extractContentFor($file, $data['html'])
                        );
                    }
                }
            }
        }

        $arg = ['success' => $success];
        if ($success === false) {
            $arg['errorDatas'] = $errorDatas;
        }
        $this->redirect('configure', null, null, $arg);
    }

    /**
     * @param \TYPO3\CMS\Core\Resource\File $templateFile
     * @param \TYPO3\CMS\Core\Resource\File $styleFile
     * @return string
     */
    protected function getMjmlFor(File $templateFile, File $styleFile)
    {
        $subpartIdentifier = $this->getSubpartIdentifier($templateFile->getNameWithoutExtension());
        $templateContent = $templateFile->getContents();
        $styleContent = $styleFile->getContents();
        return <<<EOT
<mjml>
    <mj-head>
        <mj-title>Titre</mj-title>
        $styleContent
    </mj-head>
    <mj-body>
    <!--$subpartIdentifier begin -->
    $templateContent
    <!--$subpartIdentifier end -->
    </mj-body>
</mjml>
EOT;
    }

    /**
     * @param \TYPO3\CMS\Core\Resource\File $templateFile
     * @param string $content
     * @return string
     */
    protected function extractContentFor(File $templateFile, $content = '')
    {
        $content = $this->markerService->getSubpart(
            $content,
            $this->getSubpartIdentifier($templateFile->getNameWithoutExtension())
        );
        $content = str_replace('<!-- htmlmin:ignore -->', '', $content);
        return trim($content);
    }

    /**
     * Wrap subpart identifier with marker enclosure
     * 
     * @param string $subpart
     * @return string
     */
    protected function getSubpartIdentifier($subpart)
    {
        return '###' . $subpart . '###';
    }
}
