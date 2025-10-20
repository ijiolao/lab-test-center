<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Test;
use Illuminate\Http\Request;

class TestController extends Controller
{
    /**
     * Display a listing of available tests (public)
     */
    public function index(Request $request)
    {
        $query = Test::active();

        // Filter by category
        if ($request->has('category') && $request->category != '') {
            $query->where('category', $request->category);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'name');
        $sortDirection = $request->input('sort_direction', 'asc');

        if (in_array($sortBy, ['name', 'price', 'turnaround_days'])) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('category')->orderBy('name');
        }

        $tests = $query->paginate(12);

        // Get all categories for filter
        $categories = Test::getCategories();

        return view('patient.tests.index', compact('tests', 'categories'));
    }

    /**
     * Display the specified test
     */
    public function show(Test $test)
    {
        if (!$test->is_active) {
            abort(404);
        }

        // Get related tests in same category
        $relatedTests = Test::active()
            ->where('category', $test->category)
            ->where('id', '!=', $test->id)
            ->limit(4)
            ->get();

        return view('patient.tests.show', compact('test', 'relatedTests'));
    }

    /**
     * Get test details (AJAX)
     */
    public function details(Test $test)
    {
        if (!$test->is_active) {
            return response()->json(['error' => 'Test not found'], 404);
        }

        return response()->json([
            'id' => $test->id,
            'name' => $test->name,
            'code' => $test->code,
            'description' => $test->description,
            'price' => $test->price,
            'formatted_price' => $test->formatted_price,
            'specimen_type' => $test->specimen_type,
            'turnaround_days' => $test->turnaround_days,
            'fasting_required' => $test->fasting_required,
            'preparation_instructions' => $test->preparation_instructions,
            'category' => $test->category,
        ]);
    }
}