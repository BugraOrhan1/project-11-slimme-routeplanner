<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Assignment;
use App\Models\Certificate;
use App\Models\Customer;
use App\Models\Inspector;
use App\Models\User;
use App\Services\DeadlineAlerts;
use App\Services\RoutePlanner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Testdata. Stations en inspecteurs zijn fictief ("Voorbeeldweg"); de echte gegevens komen van Klink.
 * De activiteiten en tijden zijn een aanname tot Marijn het overzicht stuurt (gesprek 23-09).
 *
 * Testaccounts (alleen voor lokaal ontwikkelen): zie README.md – wachtwoord voor alle accounts: "wachtwoord".
 */
class DatabaseSeeder extends Seeder
{
    private const PASSWORD = 'wachtwoord';

    /** plaats => [lat, lng, provincie] */
    private const CITIES = [
        'Dordrecht' => [51.8133, 4.6901, 'Zuid-Holland'], 'Zwijndrecht' => [51.8175, 4.6391, 'Zuid-Holland'],
        'Sliedrecht' => [51.8217, 4.7736, 'Zuid-Holland'], 'Gorinchem' => [51.8306, 4.9742, 'Zuid-Holland'],
        'Rotterdam' => [51.9244, 4.4777, 'Zuid-Holland'], 'Barendrecht' => [51.8567, 4.5347, 'Zuid-Holland'],
        'Spijkenisse' => [51.8453, 4.3290, 'Zuid-Holland'], 'Den Haag' => [52.0705, 4.3007, 'Zuid-Holland'],
        'Delft' => [52.0116, 4.3571, 'Zuid-Holland'], 'Zoetermeer' => [52.0607, 4.4940, 'Zuid-Holland'],
        'Gouda' => [52.0115, 4.7105, 'Zuid-Holland'], 'Leiden' => [52.1601, 4.4970, 'Zuid-Holland'],
        'Alphen aan den Rijn' => [52.1293, 4.6551, 'Zuid-Holland'],
        'Breda' => [51.5719, 4.7683, 'Noord-Brabant'], 'Oosterhout' => [51.6450, 4.8597, 'Noord-Brabant'],
        'Etten-Leur' => [51.5706, 4.6356, 'Noord-Brabant'], 'Roosendaal' => [51.5308, 4.4653, 'Noord-Brabant'],
        'Bergen op Zoom' => [51.4949, 4.2911, 'Noord-Brabant'], 'Tilburg' => [51.5555, 5.0913, 'Noord-Brabant'],
        'Waalwijk' => [51.6858, 5.0700, 'Noord-Brabant'], "'s-Hertogenbosch" => [51.6978, 5.3037, 'Noord-Brabant'],
        'Oss' => [51.7650, 5.5180, 'Noord-Brabant'], 'Eindhoven' => [51.4416, 5.4697, 'Noord-Brabant'],
        'Utrecht' => [52.0907, 5.1214, 'Utrecht'], 'Amersfoort' => [52.1561, 5.3878, 'Utrecht'],
        'Amsterdam' => [52.3676, 4.9041, 'Noord-Holland'], 'Haarlem' => [52.3874, 4.6462, 'Noord-Holland'],
        'Zaandam' => [52.4420, 4.8292, 'Noord-Holland'], 'Hilversum' => [52.2292, 5.1669, 'Noord-Holland'],
        'Nijmegen' => [51.8126, 5.8372, 'Gelderland'], 'Arnhem' => [51.9851, 5.8987, 'Gelderland'],
        'Tiel' => [51.8860, 5.4296, 'Gelderland'], 'Apeldoorn' => [52.2112, 5.9699, 'Gelderland'],
        'Middelburg' => [51.4988, 3.6136, 'Zeeland'], 'Goes' => [51.5044, 3.8889, 'Zeeland'],
        'Zwolle' => [52.5168, 6.0830, 'Overijssel'], 'Almere' => [52.3508, 5.2647, 'Flevoland'],
        'Venlo' => [51.3704, 6.1724, 'Limburg'], 'Maastricht' => [50.8514, 5.6910, 'Limburg'],
    ];

    public function run(): void
    {
        mt_srand(23092026); // steeds dezelfde testdata

        // Gebruikers per rol (gesprek 23-09: planner, bedrijfsleider, technisch manager, administratie, inspecteurs)
        foreach ([
            ['Planner Klink', 'planner@klink.test', 'planner'],
            ['Bedrijfsleider Klink', 'bedrijfsleider@klink.test', 'bedrijfsleider'],
            ['Technisch manager Klink', 'manager@klink.test', 'technisch_manager'],
            ['Administratie Klink', 'administratie@klink.test', 'administratie'],
            ['Beheerder', 'beheerder@klink.test', 'beheerder'],
        ] as [$name, $email, $role]) {
            User::create(['name' => $name, 'email' => $email, 'role' => $role, 'password' => self::PASSWORD]);
        }

        // Certificaten en activiteiten (aanname – lijst volgt van Marijn)
        $basis = Certificate::create(['name' => 'Tankinspectie (basis)', 'description' => 'Geaccrediteerd voor inspectie tankinstallaties']);
        $dicht = Certificate::create(['name' => 'Dichtheidsbeproeving', 'description' => 'Lek- en dichtheidscontrole tanks en leidingen']);
        $kb = Certificate::create(['name' => 'Kathodische bescherming', 'description' => 'Potentiaal- en stroommetingen']);
        Certificate::create(['name' => 'Besloten ruimte', 'description' => 'Werken in besloten ruimten']);

        $visueel = Activity::create(['name' => 'Visuele inspectie tank', 'duration_minutes' => 10, 'per_tank' => true, 'certificate_id' => $basis->id]);
        $lek = Activity::create(['name' => 'Lekdetectie / dichtheidscontrole', 'duration_minutes' => 10, 'per_tank' => true, 'certificate_id' => $dicht->id]);
        $aflever = Activity::create(['name' => 'Controle afleverinstallatie en leidingwerk', 'duration_minutes' => 20, 'per_tank' => false, 'certificate_id' => $basis->id]);
        $kbMeting = Activity::create(['name' => 'Kathodische bescherming meting', 'duration_minutes' => 20, 'per_tank' => false, 'certificate_id' => $kb->id]);

        // Inspecteurs – elk met een eigen account (gesprek 23-09)
        $office = config('planning.office');
        $inspectors = [
            ['Jan de Vries', 'jan@klink.test', $office['address'], $office['latitude'], $office['longitude'], '#980022', [$basis, $dicht, $kb], null],
            ['Sanne Bakker', 'sanne@klink.test', 'Breda', 51.5719, 4.7683, '#1F7A8C', [$basis, $dicht], null],
            ['Mehmet Yilmaz', 'mehmet@klink.test', 'Utrecht', 52.0907, 5.1214, '#6A4C93', [$basis, $dicht, $kb], 'kb-verlopen'],
            ['Pieter Jansen', 'pieter@klink.test', 'Gouda', 52.0115, 4.7105, '#E07A00', [$basis, $kb], null],
            ['Lisa Visser', 'lisa@klink.test', 'Eindhoven', 51.4416, 5.4697, '#2E8B57', [$basis, $dicht], null],
        ];
        foreach ($inspectors as [$name, $email, $address, $lat, $lng, $color, $certificates, $note]) {
            $user = User::create(['name' => $name, 'email' => $email, 'role' => 'inspecteur', 'password' => self::PASSWORD]);
            $inspector = Inspector::create([
                'user_id' => $user->id, 'name' => $name, 'phone' => '06-'.mt_rand(10000000, 99999999),
                'start_address' => $address === $office['address'] ? $address : "Thuisadres, {$address}",
                'start_latitude' => $lat, 'start_longitude' => $lng, 'color' => $color,
                'work_start' => '07:30', 'work_end' => '16:30',
            ]);
            foreach ($certificates as $certificate) {
                // Mehmet: certificaat kathodische bescherming is verlopen (test voor "ongeldige certificaten")
                $validUntil = $note === 'kb-verlopen' && $certificate->is($kb)
                    ? now()->subMonth()->toDateString()
                    : now()->addMonths(mt_rand(3, 30))->toDateString();
                $inspector->certificates()->attach($certificate->id, ['valid_until' => $validUntil]);
            }
        }

        // Klanten: grote maatschappijen (gesprek 23-09)
        $customers = collect([
            Customer::create(['name' => 'Shell', 'contact_person' => 'Contractbeheer Shell', 'color' => '#D9A300']),
            Customer::create(['name' => 'BP', 'contact_person' => 'Contractbeheer BP', 'color' => '#2E8B3A']),
            Customer::create(['name' => 'TotalEnergies', 'contact_person' => 'Contractbeheer TotalEnergies', 'color' => '#1F5BA8']),
        ]);

        // Stations: meer in de regio rond Dordrecht, maar door heel Nederland
        $number = 1;
        foreach (self::CITIES as $city => [$lat, $lng, $province]) {
            $count = $province === 'Zuid-Holland' || $province === 'Noord-Brabant' ? mt_rand(2, 3) : mt_rand(1, 2);
            for ($i = 0; $i < $count; $i++) {
                $customer = $customers[mt_rand(0, 2)];
                $suffix = ['Noord', 'Zuid', 'Oost', 'West', 'Centrum', 'A-weg'][mt_rand(0, 5)];
                $location = $customer->locations()->create([
                    'name' => "{$customer->name} {$city} {$suffix}",
                    'address' => 'Voorbeeldweg '.$number++,
                    'city' => $city,
                    'region' => $province,
                    'latitude' => $lat + mt_rand(-250, 250) / 10000,
                    'longitude' => $lng + mt_rand(-350, 350) / 10000,
                    'open_from' => mt_rand(0, 4) === 0 ? '08:00' : null,
                    'open_until' => mt_rand(0, 4) === 0 ? '18:00' : null,
                ]);

                // Altijd diesel en benzine, soms Euro 98 / AdBlue als losse tanks
                $products = ['Diesel', 'Euro 95'];
                if (mt_rand(0, 1)) {
                    $products[] = 'Euro 98';
                }
                if (mt_rand(0, 4) === 0) {
                    $products[] = 'AdBlue';
                }
                foreach ($products as $product) {
                    $location->tanks()->create([
                        'product' => $product,
                        'kind' => 'ondergronds',
                        'capacity_liters' => [20000, 30000, 40000, 50000][mt_rand(0, 3)],
                        'last_inspection' => now()->subYear()->addDays(mt_rand(-30, 60))->toDateString(),
                    ]);
                }

                // Opdracht uit het jaarcontract: deadline van de klant, per maand gepland
                $deadline = Carbon::today()->addDays(mt_rand(-4, 75));
                $assignment = Assignment::create([
                    'location_id' => $location->id,
                    'status' => 'open',
                    'deadline' => $deadline->toDateString(),
                    'plan_month' => $deadline->copy()->startOfMonth()->toDateString(),
                    'priority' => mt_rand(0, 6) === 0 ? 1 : 2,
                ]);
                $activities = [$visueel->id, $lek->id, $aflever->id];
                if (mt_rand(0, 2) === 0) {
                    $activities[] = $kbMeting->id;
                }
                $assignment->activities()->sync($activities);
            }
        }

        // Voorbeeld: goedgekeurde route voor Jan vandaag (of de eerstvolgende werkdag)
        $day = now()->isWeekend() ? now()->nextWeekday() : now();
        $jan = Inspector::where('name', 'Jan de Vries')->first();
        $planner = User::where('role', 'planner')->first();
        $routePlanner = app(RoutePlanner::class);
        $routePlanner->propose($day->toDateString(), [$jan->id], $planner->id)
            ->each(fn ($route) => $routePlanner->approve($route, $planner->id));

        app(DeadlineAlerts::class)->generate();
    }
}
