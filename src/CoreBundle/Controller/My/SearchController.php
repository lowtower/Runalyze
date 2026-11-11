<?php

namespace Runalyze\Bundle\CoreBundle\Controller\My;

use Frontend;
use Runalyze\Bundle\CoreBundle\Component\Search\SearchFormFields;
use Runalyze\Bundle\CoreBundle\Component\Search\SearchResults;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class SearchController extends AbstractController
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly ParameterBagInterface $parameterBag,
        private readonly RequestStack $requestStack,
        private readonly ConfigurationManager $configurationManager
    ) {}

    #[Route(path: '/my/search', name: 'my-search', methods: ['GET', 'POST'])]
    public function searchAction(Request $request): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        // The search code reads the superglobals directly. Keep them in sync with the
        // current request - a no-op for regular requests handled by the web server,
        // required for sub-requests and the test client, where the superglobals are
        // not populated.
        $_GET = $request->query->all();
        $_POST = $request->request->all();

        new Frontend($this->tokenStorage, $this->parameterBag, $this->requestStack);

        $showResults = ! empty($_POST);

        if (isset($_GET['get']) && $_GET['get'] == 'true') {
            $_POST = array_merge($_POST, $_GET);
            $showResults = true;

            $this->transformOldParamsToNewParams();
        }

        $searchResults = new SearchResults($this->configurationManager, $showResults);

        if ($showResults && $searchResults->multiEditorRequested()) {
            return $this->redirectToRoute('multi-editor', [
                'ids' => implode(',', $searchResults->getIdsForMultiEditor()),
            ]);
        }

        $showForm = empty($_POST) || $request->query->get('get') == 'true';

        if ($showForm) {
            $this->setDefaultValues();
        }

        return $this->render('my/search.html.twig', [
            'showForm' => $showForm,
            'fields' => new SearchFormFields($this->configurationManager)->fields(),
            'results' => $searchResults->templateData(),
        ]);
    }

    private function setDefaultValues(): void
    {
        $_POST['sportid'] ??= array_map(fn (\Runalyze\Model\Sport\Entity $Sport): int => (int) $Sport->id(), \Runalyze\Context::Factory()->allSports());

        $_POST['date-from'] ??= date('d.m.Y', START_TIME);

        $_POST['date-to'] ??= date('d.m.Y');
    }

    private function transformOldParamsToNewParams(): void
    {
        if (isset($_POST['val']) && is_array($_POST['val'])) {
            foreach ($_POST['val'] as $key => $value) {
                $_POST[$key] = $value;
            }
        }

        $oldToNewParamMap = [
            'time-gt' => 'date-from',
            'time-lt' => 'date-to',
            'order' => 'search-sort-by',
            'sort' => 'search-sort-order',
        ];

        foreach ($oldToNewParamMap as $oldParam => $newParam) {
            if (isset($_POST[$oldParam])) {
                $_POST[$newParam] = $_POST[$oldParam];
            }
        }
    }
}
