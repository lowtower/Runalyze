<?php

namespace Runalyze\Bundle\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class CallController extends AbstractController
{
    use AppServiceSubscriberTrait;

    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private ParameterBagInterface $parameterBag
    ) {
    }

    /**
     * @Route("/call/call.DataBrowser.display.php")
     * @Route("databrowser", name="databrowser")
     */
    public function dataBrowserAction(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $Frontend = new \Frontend(true, $this->tokenStorage, $this->parameterBag);
        $DataBrowser = new \DataBrowser();
        $DataBrowser->display();

        return new Response();
    }

    /**
     * @Route("/call/call.garminCommunicator.php")
     * @Route("/upload/garminCommunicator")
     */
    public function garminCommunicatorAction(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('import/garmin_communicator.html.twig', [
            'garminAPIKey' => $this->parameterBag->get('app.garmin_api_key'),
        ]);
    }

    /**
     * @Route("/call/savePng.php", methods={"POST"})
     */
    public function savePngAction(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        header('Content-type: image/png');
        header('Content-Disposition: attachment; filename=' . strtolower(str_replace(' ', '_', $_POST['filename'])));

        $encodeData = substr($_POST['image'], strpos($_POST['image'], ',') + 1);

        return new Response(base64_decode($encodeData));
    }

    /**
     * @Route("/settings", name="settings")
     */
    public function windowConfigAction(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $Frontend = new \Frontend(true, $this->tokenStorage, $this->parameterBag);
        $ConfigTabs = new \ConfigTabs();
        $ConfigTabs->addDefaultTab(new \ConfigTabGeneral());
        $ConfigTabs->addTab(new \ConfigTabPlugins());
        $ConfigTabs->display();

        echo \Ajax::wrapJSforDocumentReady('Runalyze.Overlay.removeClasses();');

        return new Response();
    }

    /**
     * @Route("/call/ajax.change.Config.php", methods={"GET"})
     */
    public function ajaxChanceConfigAction(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $Frontend = new \Frontend(true, $this->tokenStorage, $this->parameterBag);

        switch ($_GET['key']) {
            case 'garmin-ignore':
                \Runalyze\Configuration::ActivityForm()->ignoreActivityID($_GET['value']);
                break;

            case 'leaflet-layer':
                \Runalyze\Configuration::ActivityView()->updateLayer($_GET['value']);
                break;

            default:
                if (str_starts_with($_GET['key'], 'show-')) {
                    $key = substr($_GET['key'], 5);
                    \Runalyze\Configuration::ActivityForm()->update($key, $_GET['value']);
                }
        }

        return new Response();
    }

    /**
     * @Route("/my/search", name="my-search")
     */
    public function windowSearchAction(): \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $Frontend = new \Frontend(false, $this->tokenStorage, $this->parameterBag);
        $showResults = ! empty($_POST);

        if (isset($_GET['get']) && $_GET['get'] == 'true') {
            $_POST = array_merge($_POST, $_GET);
            $showResults = true;

            \SearchFormular::transformOldParamsToNewParams();
        }

        if (empty($_POST) || Request::createFromGlobals()->query->get('get') == 'true') {
            echo '<div class="panel-heading">';
            echo '<h1>' . __('Search for activities') . '</h1>';
            echo '</div>';

            $Formular = new \SearchFormular($this->generateUrl('my-search'));
            $Formular->display();
        }

        $Results = new \SearchResults($showResults);

        if ($showResults && $Results->multiEditorRequested()) {
            return $this->redirectToRoute('multi-editor', [
                'ids' => implode(',', $Results->getIdsForMultiEditor()),
            ]);
        }

        $Results->display();

        return new Response();
    }

    protected function getPlotSumData(): \PlotSumData
    {
        $Request = Request::createFromGlobals();

        if ($Request->query->get('y') === null) {
            $_GET['y'] = \PlotSumData::LAST_12_MONTHS;
        }

        return $Request->query->get('type', 'month') == 'week' ? new \PlotWeekSumData() : new \PlotMonthSumData();
    }

    /**
     * @Route("/call/window.plotSumData.php")
     */
    public function windowsPlotSumDataAction(): Response
    {
        $Frontend = new \Frontend(false, $this->tokenStorage, $this->parameterBag);
        $this->getPlotSumData()
            ->display();

        return new Response();
    }

    /**
     * @Route("/call/login.php")
     */
    public function loginAction(): Response
    {
        return $this->render('login/ajax_not_logged_in.html.twig');
    }
}
