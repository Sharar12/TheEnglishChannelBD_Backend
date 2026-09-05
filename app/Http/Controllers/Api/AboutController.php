<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AboutPage;
use Illuminate\Http\Request;

class AboutController extends Controller
{
    public function show()
    {
        $about = AboutPage::first();

        if (!$about) {
            $about = AboutPage::create([
                'title' => 'About The English Channel BD',
                'hero_description' => 'Your premier destination for English literature, language courses, and educational resources.',
                'our_story' => 'Founded with a passion for English education, The English Channel BD has grown from a small community initiative into a trusted online destination for learners and book lovers. Our journey began with a simple belief that quality English education deserves to be accessible to everyone.',
                'our_mission' => 'To connect learners with exceptional books and courses while fostering English proficiency and a love for reading across all ages and backgrounds.',
                'our_values' => 'We believe in quality over quantity, carefully curating our collection of books and courses to ensure every resource meets our high standards. Our commitment to student success drives everything we do.',
                'contact_email' => 'contact@englishchannelbd.com',
                'contact_phone' => '+880 1XXX-XXXXXX',
                'contact_address' => 'Dhaka, Bangladesh',
            ]);
        }

        return response()->json($about);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'hero_description' => 'sometimes|string',
            'our_story' => 'sometimes|string',
            'our_mission' => 'sometimes|string',
            'our_values' => 'sometimes|string',
            'contact_email' => 'nullable|string|email',
            'contact_phone' => 'nullable|string',
            'contact_address' => 'nullable|string',
        ]);

        $about = AboutPage::first();

        if (!$about) {
            $about = AboutPage::create($validated);
        } else {
            $about->update($validated);
        }

        return response()->json([
            'about' => $about,
            'message' => 'About page updated successfully',
        ]);
    }
}
