<?php

namespace MageSuite\CmsSearch\Model\Page\Indexer\Fulltext\Action;

class Full extends \Smile\ElasticsuiteCms\Model\Page\Indexer\Fulltext\Action\Full
{
    /**
     * @var \Smile\ElasticsuiteCms\Model\ResourceModel\Page\Indexer\Fulltext\Action\Full
     */
    private $resourceModel;

    /**
     * @var \Magento\Cms\Model\Template\FilterProvider
     */
    private $filterProvider;

    /**
     * @var \MageSuite\ContentConstructorFrontend\Service\CmsPageRenderer
     */
    private $cmsPageRenderer;

    /**
     * Constructor.
     *
     * @param \Smile\ElasticsuiteCms\Model\ResourceModel\Page\Indexer\Fulltext\Action\Full $resourceModel Indexer resource model.
     * @param \Magento\Cms\Model\Template\FilterProvider $filterProvider Model template filter provider.
     */
    public function __construct(
        \Smile\ElasticsuiteCms\Model\ResourceModel\Page\Indexer\Fulltext\Action\Full $resourceModel,
        \Magento\Cms\Model\Template\FilterProvider $filterProvider,
        \MageSuite\ContentConstructorFrontend\Service\CmsPageRenderer $pageRenderer
    )
    {
        $this->resourceModel = $resourceModel;
        $this->filterProvider = $filterProvider;
        $this->cmsPageRenderer = $pageRenderer;
    }

    /**
     * Get data for a list of cms in a store id.
     *
     * @param integer $storeId Store id.
     * @param array|null $cmsPageIds List of cms page ids.
     *
     * @return \Traversable
     */
    public function rebuildStoreIndex($storeId, $cmsPageIds = null)
    {
        $lastCmsPageId = 0;

        do {
            $cmsPages = $this->getSearchableCmsPage($storeId, $cmsPageIds, $lastCmsPageId);
            foreach ($cmsPages as $pageData) {
                $pageData = $this->processPageData($pageData, $storeId);
                $lastCmsPageId = (int)$pageData['page_id'];
                yield $lastCmsPageId => $pageData;
            }
        } while (!empty($cmsPages));
    }

    /**
     * Load a bulk of cms page data.
     *
     * @param int $storeId Store id.
     * @param string $cmsPageIds Cms page ids filter.
     * @param integer $fromId Load product with id greater than.
     * @param integer $limit Number of product to get loaded.
     *
     * @return array
     */
    private function getSearchableCmsPage($storeId, $cmsPageIds = null, $fromId = 0, $limit = 100)
    {
        return $this->resourceModel->getSearchableCmsPage($storeId, $cmsPageIds, $fromId, $limit);
    }

    /**
     * Parse template processor cms page content
     *
     * @param array $pageData Cms page data.
     *
     * @return array
     */
    private function processPageData($pageData, $storeId)
    {
        if (isset($pageData['content'])) {
            $pageData['content'] = $this->filterProvider->getPageFilter()->filter($pageData['content']);
        }

        $content = $this->cmsPageRenderer->renderPageContents($pageData['layout_update_xml'], $storeId);
        $pageData['content'] = $this->removeHtmlTagsAndWhitespaces($content);

        return $pageData;
    }

    private function removeHtmlTagsAndWhitespaces($content)
    {
        $content = strip_tags($content);
        $content = htmlspecialchars_decode($content);
        $content = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $content)));

        return $content;
    }
}
