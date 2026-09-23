@extends('layouts.app')
@section('title', 'Review assignments')
@section('content')
    <div class="page-heading"><div><p class="eyebrow">Reviewer Module</p><h1>Review assignments</h1><p>Review assessment results, exchange comments, and record completion.</p></div></div>
    <div class="card">
        <div class="table-toolbar"><h2>Assignment register</h2><span>{{ $reviews->count() }} {{ $reviews->count() === 1 ? 'review' : 'reviews' }}</span></div>
        @if($reviews->isEmpty())
            <div class="empty-state"><x-icon name="review" size="36" /><h2>No review assignments are available.</h2><p>Assessments will appear here when they are assigned for review.</p></div>
        @else
            <div class="table-responsive" role="region" aria-label="Review assignments" tabindex="0">
                <table class="table align-middle"><thead><tr><th scope="col">No.</th><th scope="col">Assessor</th><th scope="col">Reviewer</th><th scope="col">Status</th><th scope="col">Overall maturity</th><th scope="col">Updated</th><th scope="col"><span class="visually-hidden">Actions</span></th></tr></thead><tbody>
                    @foreach($reviews as $review)
                        <tr>
                            <td>
                                @if($review->status === 'pending' && ! auth()->user()->isAdmin())
                                    {{ $loop->iteration }}
                                @else
                                    <a class="table-link" href="{{ route('reviews.show', $review) }}">{{ $loop->iteration }}</a>
                                @endif
                            </td>
                            <td>{{ $review->assessment->user->name }}</td>
                            <td>{{ $review->reviewer?->name ?? 'Unassigned' }}</td>
                            <td><x-status-badge :status="$review->status" /></td>
                            <td>{{ $review->assessment->overall_maturity_level ?? 'Not calculated' }}</td>
                            <td class="text-nowrap"><time datetime="{{ $review->updated_at->toIso8601String() }}">{{ $review->updated_at->format('d M Y') }}</time></td>
                            <td>
                                @if($review->status === 'pending' && ! auth()->user()->isAdmin())
                                    <form method="POST" action="{{ route('reviews.update', $review) }}" data-loading-form>
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="action" value="accept">
                                        <button class="btn btn-sm btn-primary text-nowrap" type="submit" data-loading-label="Accepting…" aria-label="Accept review for assessment {{ $loop->iteration }}"><x-icon name="check" size="16" /> Accept review</button>
                                    </form>
                                @else
                                    <a class="btn btn-sm btn-outline-primary text-nowrap" href="{{ route('reviews.show', $review) }}" aria-label="Open review for assessment {{ $loop->iteration }}">Open review <x-icon name="arrow" size="16" /></a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody></table>
            </div>
        @endif
    </div>
@endsection
