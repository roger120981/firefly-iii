<?php namespace FireflyIII\Http\Controllers;

use Auth;
use Carbon\Carbon;
use FireflyIII\Http\Requests;
use FireflyIII\Http\Requests\CategoryFormRequest;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Input;
use Redirect;
use Session;
use View;
use URL;

/**
 * Class CategoryController
 *
 * @package FireflyIII\Http\Controllers
 */
class CategoryController extends Controller
{

    /**
     *
     */
    public function __construct()
    {
        View::share('title', 'Categories');
        View::share('mainTitleIcon', 'fa-bar-chart');
    }

    /**
     * @return $this
     */
    public function create()
    {
        // put previous url in session if not redirect from store (not "create another").
        if (Session::get('categories.create.fromStore') !== true) {
            Session::put('categories.create.url', URL::previous());
        }
        Session::forget('categories.create.fromStore');

        return view('categories.create')->with('subTitle', 'Create a new category');
    }

    /**
     * @param Category $category
     *
     * @return \Illuminate\View\View
     */
    public function delete(Category $category)
    {
        $subTitle = 'Delete category' . e($category->name) . '"';

        // put previous url in session
        Session::put('categories.delete.url', URL::previous());

        return view('categories.delete', compact('category', 'subTitle'));
    }

    /**
     * @param Category $category
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Category $category, CategoryRepositoryInterface $repository)
    {

        $name = $category->name;
        $repository->destroy($category);

        Session::flash('success', 'The  category "' . e($name) . '" was deleted.');

        return Redirect::to(Session::get('categories.delete.url'));
    }

    /**
     * @param Category $category
     *
     * @return $this
     */
    public function edit(Category $category)
    {
        $subTitle = 'Edit category "' . e($category->name) . '"';

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (Session::get('categories.edit.fromUpdate') !== true) {
            Session::put('categories.edit.url', URL::previous());
        }
        Session::forget('categories.edit.fromUpdate');

        return view('categories.edit', compact('category', 'subTitle'));

    }

    /**
     * @return $this
     */
    public function index()
    {
        $categories = Auth::user()->categories()->orderBy('name', 'ASC')->get();

        $categories->each(
            function (Category $category) {
                $latest = $category->transactionjournals()
                                   ->orderBy('transaction_journals.date', 'DESC')
                                   ->orderBy('transaction_journals.order', 'ASC')
                                   ->orderBy('transaction_journals.id', 'DESC')
                                   ->first();
                if ($latest) {
                    $category->lastActivity = $latest->date;
                }
            }
        );

        return view('categories.index', compact('categories'));
    }

    /**
     * @return \Illuminate\View\View
     */
    public function noCategory()
    {
        $start = Session::get('start', Carbon::now()->startOfMonth());
        $end   = Session::get('end', Carbon::now()->startOfMonth());
        $list  = Auth::user()
                     ->transactionjournals()
                     ->leftJoin('category_transaction_journal', 'category_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
                     ->whereNull('category_transaction_journal.id')
                     ->before($end)
                     ->after($start)
                     ->orderBy('transaction_journals.date', 'DESC')
                     ->orderBy('transaction_journals.order', 'ASC')
                     ->orderBy('transaction_journals.id', 'DESC')
                     ->get(['transaction_journals.*']);

        $subTitle = 'Transactions without a category between ' . $start->format('jS F Y') . ' and ' . $end->format('jS F Y');

        return view('categories.noCategory', compact('list', 'subTitle'));
    }

    /**
     * @param Category $category
     *
     * @return $this
     */
    public function show(Category $category, CategoryRepositoryInterface $repository)
    {
        $hideCategory = true; // used in list.
        $page         = intval(Input::get('page'));
        $offset       = $page > 0 ? $page * 50 : 0;
        $set          = $category->transactionJournals()->withRelevantData()->take(50)->offset($offset)
                                 ->orderBy('transaction_journals.date', 'DESC')
                                 ->orderBy('transaction_journals.order', 'ASC')
                                 ->orderBy('transaction_journals.id', 'DESC')
                                 ->get(
                                     ['transaction_journals.*']
                                 );
        $count        = $category->transactionJournals()->count();

        $journals = new LengthAwarePaginator($set, $count, 50, $page);

        return view('categories.show', compact('category', 'journals', 'hideCategory'));
    }

    /**
     * @param CategoryFormRequest         $request
     * @param CategoryRepositoryInterface $repository
     *
     * @return mixed
     */
    public function store(CategoryFormRequest $request, CategoryRepositoryInterface $repository)
    {
        $categoryData = [
            'name' => $request->input('name'),
            'user' => Auth::user()->id,
        ];
        $category     = $repository->store($categoryData);

        Session::flash('success', 'New category "' . $category->name . '" stored!');

        if (intval(Input::get('create_another')) === 1) {
            Session::put('categories.create.fromStore', true);
            return Redirect::route('categories.create')->withInput();
        }

<<<<<<< HEAD
        if (intval(Input::get('create_another')) === 1) {
            return Redirect::route('categories.create');
        }

        return Redirect::route('categories.index');
=======
        // redirect to previous URL.
        return Redirect::to(Session::get('categories.create.url'));
>>>>>>> release/3.3.6

    }


    /**
     * @param Category                    $category
     * @param CategoryFormRequest         $request
     * @param CategoryRepositoryInterface $repository
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Category $category, CategoryFormRequest $request, CategoryRepositoryInterface $repository)
    {
        $categoryData = [
            'name' => $request->input('name'),
        ];

        $repository->update($category, $categoryData);

        Session::flash('success', 'Category "' . $category->name . '" updated.');

        if (intval(Input::get('return_to_edit')) === 1) {
            Session::put('categories.edit.fromUpdate', true);
            return Redirect::route('categories.edit', $category->id);
        }

        // redirect to previous URL.
        return Redirect::to(Session::get('categories.edit.url'));

    }

}
