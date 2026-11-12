<?php

namespace Tests\Feature;

use App\Models\HardwareLog;
use App\Models\Pc;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PcInventoryTest extends TestCase
{
    use RefreshDatabase;

    private string $apiKey = '52dd6df51a1a9c66d15dd26f812b6a67412ac2079ae4bd79';
    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminUser = User::factory()->create([
            'email' => 'admin@kantor.local',
        ]);
    }

    /**
     * Test unauthenticated guests are redirected to login.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/pcs');

        $response->assertRedirect(route('login'));
    }

    /**
     * Test login view renders correctly.
     */
    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Inventaris PC Kantor');
    }

    /**
     * Test user can log in with valid credentials.
     */
    public function test_user_can_authenticate(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('pcs.index'));
    }

    /**
     * Test dashboard view returns 200 for authenticated user.
     */
    public function test_dashboard_can_be_rendered(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/pcs');

        $response->assertStatus(200);
        $response->assertSee('Inventaris PC Kantor');
    }

    /**
     * Test export PC inventory to CSV.
     */
    public function test_can_export_pcs_to_csv(): void
    {
        Pc::create([
            'uid' => 'csv-export-uid',
            'hostname' => 'FINANCE-EXPORT',
            'alias' => 'PC Kasir Export',
        ]);

        $response = $this->actingAs($this->adminUser)->get('/pcs/export/csv');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    /**
     * Test PC detail view can be rendered with specifications.
     */
    public function test_pc_detail_can_be_rendered(): void
    {
        $pc = Pc::create([
            'uid' => 'detail-test-uid',
            'hostname' => 'OFFICE-DESK-01',
            'alias' => 'PC Kasir Utama',
            'cpu' => 'Intel Core i5-12400',
            'ram_gb' => 16.0,
            'disks' => [
                'physical' => [['model' => 'Samsung 980', 'type' => 'SSD', 'size_gb' => 500]],
                'partitions' => [['drive' => 'C:\\', 'total_gb' => 500.0, 'free_gb' => 300.0]],
            ],
            'last_poll_at' => now(),
            'last_report_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)->get("/pcs/{$pc->id}");

        $response->assertStatus(200);
        $response->assertSee('PC Kasir Utama');
        $response->assertSee('OFFICE-DESK-01');
        $response->assertSee('Intel Core i5-12400');
    }

    /**
     * Test printable QR sticker label.
     */
    public function test_can_render_print_label(): void
    {
        $pc = Pc::create([
            'uid' => 'label-test-uid',
            'hostname' => 'KASIR-01',
            'alias' => 'PC Kasir Depan',
        ]);

        $response = $this->actingAs($this->adminUser)->get("/pcs/{$pc->id}/print-label");

        $response->assertStatus(200);
        $response->assertSee('PC Kasir Depan');
        $response->assertSee('KASIR-01');
    }

    /**
     * Test downloading RDP shortcut file.
     */
    public function test_can_download_rdp_file(): void
    {
        $pc = Pc::create([
            'uid' => 'rdp-test-uid',
            'hostname' => 'REMOTE-PC',
            'ip' => '192.168.10.88',
        ]);

        $response = $this->actingAs($this->adminUser)->get("/pcs/{$pc->id}/remote-rdp");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/x-rdp');
    }

    /**
     * Test updating PC alias, department, location, and notes.
     */
    public function test_can_update_pc_alias_and_metadata(): void
    {
        $pc = Pc::create([
            'uid' => 'meta-test-uid',
            'hostname' => 'RAW-PC-NAME',
        ]);

        $response = $this->actingAs($this->adminUser)->put("/pcs/{$pc->id}", [
            'alias' => 'PC Desain Grafis 01',
            'stock_status' => 'bebas',
            'department' => 'Marketing & Design',
            'location' => 'Lantai 2 Meja 12',
            'notes' => 'Upgrade RAM 32GB bulan depan',
        ]);

        $response->assertRedirect();

        $pc->refresh();
        $this->assertSame('PC Desain Grafis 01', $pc->alias);
        $this->assertSame('bebas', $pc->stock_status);
        $this->assertSame('Stock Bebas', $pc->stock_status_label);
        $this->assertSame('Marketing & Design', $pc->department);
        $this->assertSame('Lantai 2 Meja 12', $pc->location);
        $this->assertSame('Upgrade RAM 32GB bulan depan', $pc->notes);
        $this->assertSame('PC Desain Grafis 01', $pc->display_name);
    }

    /**
     * Test deleting a PC from inventory.
     */
    public function test_can_delete_pc_from_inventory(): void
    {
        $pc = Pc::create([
            'uid' => 'del-test-uid',
            'hostname' => 'OLD-BROKEN-PC',
            'alias' => 'PC Rusak',
        ]);

        $response = $this->actingAs($this->adminUser)->delete("/pcs/{$pc->id}");

        $response->assertRedirect(route('pcs.index'));
        $this->assertDatabaseMissing('pcs', ['id' => $pc->id]);
    }

    /**
     * Test API reject requests without valid key.
     */
    public function test_api_rejects_unauthorized_requests(): void
    {
        $response = $this->postJson('/api/poll', [
            'uid' => 'test-uid-1',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test poll triggers sync when PC is not yet registered.
     */
    public function test_poll_requests_sync_for_unregistered_pc(): void
    {
        $response = $this->withHeader('X-API-Key', $this->apiKey)
            ->postJson('/api/poll', [
                'uid' => 'new-pc-01',
                'hostname' => 'OFFICE-PC-01',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['sync' => true]);
    }

    /**
     * Test report saves PC hardware specs to database.
     */
    public function test_report_stores_pc_data(): void
    {
        $response = $this->withHeader('X-API-Key', $this->apiKey)
            ->postJson('/api/report', [
                'uid' => 'pc-unique-123',
                'hostname' => 'FINANCE-PC',
                'alias' => 'PC Finance 01',
                'department' => 'Finance Dept',
                'location' => 'Lantai 1',
                'ip' => '192.168.1.55',
                'mac' => 'aa:bb:cc:dd:ee:ff',
                'login_user' => 'KANTOR\\staff1',
                'manufacturer' => 'Dell Inc.',
                'model' => 'OptiPlex 7080',
                'motherboard' => 'Dell 0W2TYF',
                'bios_version' => '1.14.0',
                'serial' => 'SN123456',
                'os' => 'Windows 11 Pro',
                'cpu' => 'Intel Core i7-11700',
                'cpu_cores' => 8,
                'cpu_threads' => 16,
                'gpu' => 'NVIDIA GeForce GTX 1660 SUPER',
                'gpu_details' => [
                    ['name' => 'NVIDIA GeForce GTX 1660 SUPER', 'vram_gb' => 6.0, 'resolution' => '1920x1080 @ 100Hz'],
                ],
                'monitors' => [
                    ['manufacturer' => 'Acer', 'model' => 'EK241Y E', 'serial' => '231608FAF3W01'],
                ],
                'battery' => [
                    'is_laptop' => false,
                    'status' => 'Desktop (Tanpa Baterai)',
                ],
                'ram_gb' => 16.0,
                'ram_type' => 'DDR4',
                'ram_speed' => 3200,
                'ram_details' => [
                    ['slot' => 'DIMM1', 'size_gb' => 16.0, 'speed' => 3200, 'type' => 'DDR4'],
                ],
                'uptime_h' => 4.5,
                'physical_disks' => [
                    ['model' => 'Samsung NVMe 512GB', 'type' => 'SSD', 'size_gb' => 476],
                ],
                'partitions' => [
                    ['drive' => 'C:\\', 'total_gb' => 476.0, 'free_gb' => 250.0],
                ],
            ]);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);

        $this->assertDatabaseHas('pcs', [
            'uid' => 'pc-unique-123',
            'hostname' => 'FINANCE-PC',
            'alias' => 'PC Finance 01',
            'department' => 'Finance Dept',
            'location' => 'Lantai 1',
            'ip' => '192.168.1.55',
        ]);

        $pc = Pc::where('uid', 'pc-unique-123')->first();
        $this->assertNotNull($pc);
        $this->assertSame('PC Finance 01', $pc->alias);
        $this->assertSame('digunakan', $pc->stock_status);
        $this->assertSame('Stock Digunakan', $pc->stock_status_label);
        $this->assertSame('Finance Dept', $pc->department);
        $this->assertSame('Lantai 1', $pc->location);
        $this->assertSame('Dell 0W2TYF', $pc->motherboard);
        $this->assertSame('1.14.0', $pc->bios_version);
        $this->assertSame('NVIDIA GeForce GTX 1660 SUPER', $pc->gpu);
        $this->assertCount(1, $pc->gpu_details);
        $this->assertCount(1, $pc->monitors);
        $this->assertFalse($pc->battery['is_laptop']);
        $this->assertSame('DDR4', $pc->ram_type);
        $this->assertSame(3200, $pc->ram_speed);
        $this->assertCount(1, $pc->ram_details);
        $this->assertTrue($pc->is_online);
        $this->assertFalse($pc->sync_pending);
    }

    /**
     * Test clicking sync button sets sync_requested_at and poll returns sync: true.
     */
    public function test_sync_request_workflow(): void
    {
        $pc = Pc::create([
            'uid' => 'pc-sync-01',
            'hostname' => 'MARKETING-01',
            'last_poll_at' => now(),
            'last_report_at' => now(),
        ]);

        // Trigger sync for this PC
        $syncResponse = $this->actingAs($this->adminUser)->post("/pcs/{$pc->id}/sync");
        $syncResponse->assertRedirect();

        $pc->refresh();
        $this->assertTrue($pc->sync_pending);

        // Next poll from agent should receive sync: true
        $pollResponse = $this->withHeader('X-API-Key', $this->apiKey)
            ->postJson('/api/poll', [
                'uid' => 'pc-sync-01',
                'hostname' => 'MARKETING-01',
            ]);

        $pollResponse->assertStatus(200);
        $pollResponse->assertJson(['sync' => true]);
    }

    /**
     * Test hardware change detection records audit log.
     */
    public function test_hardware_change_generates_audit_log(): void
    {
        // First report: 16GB RAM
        $this->withHeader('X-API-Key', $this->apiKey)
            ->postJson('/api/report', [
                'uid' => 'tamper-test-pc',
                'hostname' => 'TAMPER-PC',
                'ram_gb' => 16.0,
                'cpu' => 'Intel Core i5-10400',
            ]);

        $pc = Pc::where('uid', 'tamper-test-pc')->first();
        $this->assertNotNull($pc);
        $this->assertCount(0, $pc->hardwareLogs);

        // Second report: RAM reduced to 8GB (DIMM removed)
        $this->withHeader('X-API-Key', $this->apiKey)
            ->postJson('/api/report', [
                'uid' => 'tamper-test-pc',
                'hostname' => 'TAMPER-PC',
                'ram_gb' => 8.0,
                'cpu' => 'Intel Core i5-10400',
            ]);

        $pc->refresh();
        $this->assertSame(8.0, (float) $pc->ram_gb);
        $this->assertCount(1, $pc->hardwareLogs);

        $log = $pc->hardwareLogs->first();
        $this->assertSame('RAM', $log->component);
        $this->assertSame('downgraded', $log->change_type);
        $this->assertStringContainsString('berkurang dari 16 GB menjadi 8 GB', $log->description);
    }
}
