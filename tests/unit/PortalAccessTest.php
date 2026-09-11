<?php
use App\Libraries\PortalAccess;
use CodeIgniter\Test\CIUnitTestCase;

final class PortalAccessTest extends CIUnitTestCase
{
    public function testEveryRegisteredPortalRouteHasAnAccessRule(): void
    {
        $routes = service('routes');
        $routes->loadRoutes();
        $count = 0;
        foreach (['GET', 'POST'] as $method) {
            foreach ($routes->getRoutes($method) as $path => $handler) {
                if (!str_starts_with($path, 'client_portal/') && !str_starts_with($path, 'settings/')) continue;
                $sample = preg_replace('~\([^)]*\)~', '1', $path);
                self::assertNotSame('deny', PortalAccess::requirement($sample), $method . ' ' . $path);
                $count++;
            }
        }
        self::assertGreaterThan(90, $count);
    }

    public function testSensitiveRoutesCannotFallBackToSelfService(): void
    {
        foreach (['payroll/payslipHistory', 'payroll/getAllPayslips', 'payroll/employee_details/1', 'payroll/importTimesheet', 'loans/loanEmployees', 'employees/export'] as $path) {
            self::assertNotSame('self', PortalAccess::requirement('client_portal/' . $path));
        }
        self::assertSame('release', PortalAccess::requirement('client_portal/payroll/markStatus'));
        self::assertSame('review_request', PortalAccess::requirement('client_portal/requests/update/1'));
        self::assertSame('self', PortalAccess::requirement('client_portal/help/store'));
        self::assertSame('self', PortalAccess::requirement('client_portal/payroll/getMyPayslips'));
        self::assertSame('deny', PortalAccess::requirement('client_portal/future-sensitive-endpoint'));
    }
}
