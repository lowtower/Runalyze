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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Throwable;

class CallController extends AbstractController
{
    use AppServiceSubscriberTrait;
    use LegacyOutputCaptureTrait;

    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private ParameterBagInterface $parameterBag,
        private RequestStack $requestStack
    ) {}

    /**
     * @throws Throwable if the data browser throws
     */
    #[Route(path: '/call/call.DataBrowser.display.php', methods: ['GET'])]
    #[Route(path: 'databrowser', name: 'databrowser', methods: ['GET'])]
    public function dataBrowserAction(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        new Frontend($this->tokenStorage, $this->parameterBag, $this->requestStack);

        $dataBrowserContent = $this->renderView('my/data_browser.html.twig', (new DataBrowser())->templateData());

        return $this->wrapLegacyContent($dataBrowserContent, $this->requestStack->getCurrentRequest());
    }

    /**
     * @throws BadRequestHttpException
     */
    #[Route(path: '/call/savePng.php', methods: ['POST'])]
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
     * @throws Throwable if the legacy config tabs throw
     */
    #[Route(path: '/settings', name: 'settings', methods: ['GET', 'POST'])]
    public function windowConfigAction(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        new Frontend($this->tokenStorage, $this->parameterBag, $this->requestStack);

        $legacyContent = $this->captureLegacyOutput(function (): void {
            $ConfigTabs = new ConfigTabs();
            $ConfigTabs->addDefaultTab(new ConfigTabGeneral());
            $ConfigTabs->addTab(new ConfigTabPlugins());
            $ConfigTabs->display();

            echo Ajax::wrapJSforDocumentReady('Runalyze.Overlay.removeClasses();');
        })->getContent();

        return $this->wrapLegacyContent($legacyContent, $this->requestStack->getCurrentRequest());
    }

    #[Route(path: '/call/ajax.change.Config.php', methods: ['GET'])]
    public function ajaxChanceConfigAction(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        new Frontend($this->tokenStorage, $this->parameterBag, $this->requestStack);

        $key = $request->query->get('key', '');
        $value = $request->query->get('value');

        switch ($key) {
            case 'garmin-ignore':
                if (is_string($value) && $value !== '') {
                    Configuration::ActivityForm()->ignoreActivityID($value);
                }

                break;

            case 'leaflet-layer':
                if (! is_string($value) || preg_match('/\A[A-Za-z0-9 ()_-]{1,64}\z/D', $value) !== 1) {
                    throw new BadRequestHttpException('Invalid map layer.');
                }

                Configuration::ActivityView()->updateLayer($value);

                break;

            default:
                $legendKey = str_starts_with($key, 'show-') ? substr($key, 5) : '';

                if (in_array($legendKey, ['SPORT', 'GENERAL', 'DISTANCE', 'SPLITS', 'WEATHER', 'OTHER', 'NOTES', 'PUBLIC', 'ELEVATION', 'GPS'], true) && is_string($value)) {
                    Configuration::ActivityForm()->update($legendKey, $value);
                }
        }

        return new Response();
    }

    protected function getPlotSumData(): PlotSumData
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request->query->get('y') === null) {
            $request->query->set('y', PlotSumData::LAST_12_MONTHS);
        }

        return $request->query->get('type', 'month') == 'week' ? new PlotWeekSumData() : new PlotMonthSumData();
    }

    /**
     * @throws Throwable if the legacy plot sum data throws
     */
    #[Route(path: '/call/window.plotSumData.php', methods: ['GET'])]
    public function windowsPlotSumDataAction(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        ob_start();

        try {
            new Frontend($this->tokenStorage, $this->parameterBag, $this->requestStack);
            $this->getPlotSumData()
                ->display();
            $legacyContent = ob_get_clean();
        } catch (Throwable $throwable) {
            ob_end_clean();

            throw $throwable;
        }

        return $this->wrapLegacyContent($legacyContent, $this->requestStack->getCurrentRequest());
    }

    #[Route(path: '/call/login.php', methods: ['GET', 'POST'])]
    public function loginAction(): Response
    {
        return $this->render('login/ajax_not_logged_in.html.twig');
    }
}
