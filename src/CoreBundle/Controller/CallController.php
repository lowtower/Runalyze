<?php

namespace Runalyze\Bundle\CoreBundle\Controller;

use Ajax;
use ConfigTabGeneral;
use ConfigTabPlugins;
use ConfigTabs;
use DataBrowser;
use Frontend;
use PlotMonthSumData;
use PlotSumData;
use PlotWeekSumData;
use Runalyze\Configuration;
use SearchFormular;
use SearchResults;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Throwable;

class CallController extends AbstractController
{
    use AppServiceSubscriberTrait;
    use LegacyOutputCaptureTrait;

    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private ParameterBagInterface $parameterBag
    ) {
    }

    /**
     * @Route("/call/call.DataBrowser.display.php")
     * @Route("databrowser", name="databrowser")
     *
     * @throws Throwable if the legacy data browser throws
     */
    public function dataBrowserAction(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        new Frontend(true, $this->tokenStorage, $this->parameterBag);

        return $this->captureLegacyOutput(function (): void {
            $DataBrowser = new DataBrowser();
            $DataBrowser->display();
        });
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
     *
     * @throws BadRequestHttpException
     */
    public function savePngAction(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $filename = $request->request->get('filename');
        $image = $request->request->get('image');

        if ($filename === null || $image === null) {
            throw new BadRequestHttpException('Both "filename" and "image" must be provided.');
        }

        $encodedData = substr($image, strpos($image, ',') + 1);

        $response = new Response(base64_decode($encodedData));

        $response->headers->set('Content-Type', 'image/png');
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                strtolower(str_replace(' ', '_', $filename))
            )
        );

        return $response;
    }

    /**
     * @Route("/settings", name="settings")
     *
     * @throws Throwable if the legacy config tabs throw
     */
    public function windowConfigAction(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        new Frontend(true, $this->tokenStorage, $this->parameterBag);

        return $this->captureLegacyOutput(function (): void {
            $ConfigTabs = new ConfigTabs();
            $ConfigTabs->addDefaultTab(new ConfigTabGeneral());
            $ConfigTabs->addTab(new ConfigTabPlugins());
            $ConfigTabs->display();

            echo Ajax::wrapJSforDocumentReady('Runalyze.Overlay.removeClasses();');
        });
    }

    /**
     * @Route("/call/ajax.change.Config.php", methods={"GET"})
     */
    public function ajaxChanceConfigAction(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        new Frontend(true, $this->tokenStorage, $this->parameterBag);

        $key = $request->query->get('key', '');
        $value = $request->query->get('value');

        switch ($key) {
            case 'garmin-ignore':
                Configuration::ActivityForm()->ignoreActivityID($value);
                break;

            case 'leaflet-layer':
                Configuration::ActivityView()->updateLayer($value);
                break;

            default:
                if (str_starts_with($key, 'show-')) {
                    Configuration::ActivityForm()->update(substr($key, 5), $value);
                }
        }

        return new Response();
    }

    /**
     * @Route("/my/search", name="my-search")
     *
     * @throws Throwable if the legacy search formular or results throw
     */
    public function windowSearchAction(): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        new Frontend(false, $this->tokenStorage, $this->parameterBag);

        $showResults = ! empty($_POST);

        if (isset($_GET['get']) && $_GET['get'] == 'true') {
            $_POST = array_merge($_POST, $_GET);
            $showResults = true;

            SearchFormular::transformOldParamsToNewParams();
        }

        $Results = new SearchResults($showResults);

        if ($showResults && $Results->multiEditorRequested()) {
            return $this->redirectToRoute('multi-editor', [
                'ids' => implode(',', $Results->getIdsForMultiEditor()),
            ]);
        }

        return $this->captureLegacyOutput(function () use ($Results): void {
            if (empty($_POST) || Request::createFromGlobals()->query->get('get') == 'true') {
                echo '<div class="panel-heading">';
                echo '<h1>' . __('Search for activities') . '</h1>';
                echo '</div>';

                $Formular = new SearchFormular($this->generateUrl('my-search'));
                $Formular->display();
            }

            $Results->display();
        });
    }

    protected function getPlotSumData(): PlotSumData
    {
        $Request = Request::createFromGlobals();

        if ($Request->query->get('y') === null) {
            $_GET['y'] = PlotSumData::LAST_12_MONTHS;
        }

        return $Request->query->get('type', 'month') == 'week' ? new PlotWeekSumData() : new PlotMonthSumData();
    }

    /**
     * @Route("/call/window.plotSumData.php")
     *
     * @throws Throwable if the legacy plot sum data throws
     */
    public function windowsPlotSumDataAction(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        new Frontend(false, $this->tokenStorage, $this->parameterBag);

        return $this->captureLegacyOutput(function (): void {
            $this->getPlotSumData()
                ->display();
        });
    }

    /**
     * @Route("/call/login.php")
     */
    public function loginAction(): Response
    {
        return $this->render('login/ajax_not_logged_in.html.twig');
    }
}
