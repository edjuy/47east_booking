<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Requests\StoreVenueRequest;
use App\Http\Requests\UpdateVenueRequest;
use App\Http\Resources\Admin\VenueResource;
use App\Venue;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VenueApiController extends Controller
{
    use MediaUploadingTrait;

    public function index()
    {
        abort_if(Gate::denies('venue_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return new VenueResource(Venue::with(['categories', 'tags', 'amenities'])->get());
    }

    public function store(StoreVenueRequest $request)
    {
        $venue = Venue::create($request->all());
        $venue->categories()->sync($request->input('categories', []));
        $venue->tags()->sync($request->input('tags', []));
        $venue->amenities()->sync($request->input('amenities', []));

        if ($request->input('photo', false)) {
            $venue->addMedia(storage_path('tmp/uploads/' . $request->input('photo')))->toMediaCollection('photo');
        }

        return (new VenueResource($venue))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Venue $venue)
    {
        abort_if(Gate::denies('venue_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return new VenueResource($venue->load(['categories', 'tags', 'amenities']));
    }

    public function update(UpdateVenueRequest $request, Venue $venue)
    {
        $venue->update($request->all());
        $venue->categories()->sync($request->input('categories', []));
        $venue->tags()->sync($request->input('tags', []));
        $venue->amenities()->sync($request->input('amenities', []));

        if ($request->input('photo', false)) {
            if (!$venue->photo || $request->input('photo') !== $venue->photo->file_name) {
                $venue->addMedia(storage_path('tmp/uploads/' . $request->input('photo')))->toMediaCollection('photo');
            }
        } elseif ($venue->photo) {
            $venue->photo->delete();
        }

        return (new VenueResource($venue))
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(Venue $venue)
    {
        abort_if(Gate::denies('venue_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $venue->delete();

        return response(null, Response::HTTP_NO_CONTENT);
    }
}