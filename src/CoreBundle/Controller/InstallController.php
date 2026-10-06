<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Controller;

use Runalyze\Bundle\CoreBundle\Console\Formatter\HtmlOutputFormatter;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @author Hannes Christiansen <hannes@runalyze.de>
 * @author Michael Pohl <michael@runalyze.de>
 * @package Runalyze\Bundle\CoreBundle\Controller
 */
class InstallController extends AbstractController
{
    use AppServiceSubscriberTrait;

    private const string SESSION_POSSIBLE = 'installer/possible';

    private const string SESSION_SUCCESSFUL = 'installer/successful';

    private const string SESSION_OUTPUT = 'installer/output';

    public function __construct(
        private readonly KernelInterface $kernel
    ) {
    }

    #[Route(path: '/install', name: 'install', condition: "'%app.update_disabled%' == 'no'")]
    public function installAction(Request $request): RedirectResponse|Response
    {
        $session = $request->getSession();

        if ($session->has(self::SESSION_SUCCESSFUL)) {
            return $this->redirectToRoute('install_finish');
        }

        $output = new BufferedOutput(BufferedOutput::VERBOSITY_NORMAL, true, new HtmlOutputFormatter(true));
        $exitCode = $this->runInstallerCommand('runalyze:install:check', $output);

        $session->set(self::SESSION_POSSIBLE, $exitCode === 0);

        return $this->render('system/install.html.twig', [
            'output' => '$ php bin/console runalyze:install:check' . "\n\n" . $output->fetch(),
            'installationPossible' => $exitCode === 0,
            'installationSuccessful' => false,
        ]);
    }

    #[Route(path: '/install/start', name: 'install_start', condition: "'%app.update_disabled%' == 'no'")]
    public function startAction(Request $request): RedirectResponse|Response
    {
        $session = $request->getSession();

        if (! $session->has(self::SESSION_POSSIBLE) || ! $session->get(self::SESSION_POSSIBLE)) {
            return $this->redirectToRoute('install');
        }

        if ($session->has(self::SESSION_SUCCESSFUL)) {
            return $this->redirectToRoute('install_finish');
        }

        $output = new BufferedOutput(BufferedOutput::VERBOSITY_NORMAL, true, new HtmlOutputFormatter(true));
        $exitCode = $this->runInstallerCommand('runalyze:install --skip=check', $output);
        $outputString = '$ php bin/console runalyze:install --skip=check' . "\n\n" . $output->fetch();

        if ($exitCode > 0) {
            return $this->render('system/install.html.twig', [
                'output' => $outputString,
                'installationPossible' => false,
                'installationSuccessful' => false,
            ]);
        }

        $session->set(self::SESSION_SUCCESSFUL, true);
        $session->set(self::SESSION_OUTPUT, $outputString);

        return $this->redirectToRoute('install_finish');
    }

    #[Route(path: '/install/finish', name: 'install_finish', condition: "'%app.update_disabled%' == 'no'")]
    public function finishAction(Request $request): RedirectResponse|Response
    {
        $session = $request->getSession();

        if (! $session->has(self::SESSION_SUCCESSFUL)) {
            return $this->redirectToRoute('install');
        }

        return $this->render('system/install.html.twig', [
            'output' => $session->get(self::SESSION_OUTPUT, ''),
            'installationPossible' => false,
            'installationSuccessful' => true,
        ]);
    }

    private function runInstallerCommand(string $commandLine, BufferedOutput $output): int
    {
        $app = new Application($this->kernel);
        $app->setAutoExit(false);

        $input = new StringInput($commandLine);

        $previousShellVerbosity = getenv('SHELL_VERBOSITY');
        putenv('SHELL_VERBOSITY=0');

        try {
            return $app->run($input, $output);
        } finally {
            putenv($previousShellVerbosity === false ? 'SHELL_VERBOSITY' : 'SHELL_VERBOSITY=' . $previousShellVerbosity);
        }
    }
}
