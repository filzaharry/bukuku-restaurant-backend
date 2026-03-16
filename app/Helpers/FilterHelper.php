<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use Carbon\Carbon;

class FilterHelper
{
    /**
     * Apply global filter, sorting, and manual pagination to an Eloquent query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public static function filterAndPaginate($query, Request $request, array $searchableFields = [])
    {
        $limit = $request->query('limit', 10);
        $page = $request->query('page', 1);
        $sort = $request->query('sort', null);
        $search = $request->query('search');

        // Handle general search
        if (!empty($search) && !empty($searchableFields)) {
            $query->where(function ($q) use ($searchableFields, $search) {
                foreach ($searchableFields as $field) {
                    $q->orWhere($field, 'LIKE', '%' . $search . '%');
                }
            });
        }

        // Specific filter
        $param = $request->except(['page', 'limit', 'sort', 'search', 'deleted_by_id', 'statuses']);

        foreach ($param as $key => $value) {
            if (!empty($value)) {
                if ($key === 'created_at') {
                    $value = Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
                    $query->whereDate($key, '=', $value);
                } else {
                    $query->where($key, 'LIKE', '%' . $value . '%');
                }
            }
        }

        // Soft delete filter
        $table = $query->getModel()->getTable();
        $query->whereNull($table . '.deleted_by_id');

        // Sorting
        if ($sort) {
            self::applySorts($query, $sort);
        }

        $total = $query->count();
        $offset = ($page - 1) * $limit;
        $data = $query->offset($offset)->limit($limit)->get();

        return [
            'data' => $data,
            'pagination' => [
                'page' => (int) $page,
                'totalData' => $total,
                'totalPage' => (int) ceil($total / $limit),
                'totalPerPage' => (int) $limit
            ]
        ];
    }


    /**
     * Apply sorting to the query based on the sort string.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $sort
     * @return void
     */
    private static function applySorts($query, $sort)
    {
        $sorts = explode(',', $sort);
        foreach ($sorts as $sortField) {
            $sortField = trim($sortField);
            if ($sortField === '') {
                continue;
            }
            $direction = 'asc';
            if (substr($sortField, 0, 1) === '-') {
                $direction = 'desc';
                $sortField = substr($sortField, 1);
            }
            if ($sortField !== '') {
                $query->orderBy($sortField, $direction);
            }
        }
    }
}
