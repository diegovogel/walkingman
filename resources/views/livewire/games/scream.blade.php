<?php

use App\Models\ScreamGameResult;
use Illuminate\Http\UploadedFile;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Illuminate\Http\Request;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public ?ScreamGameResult $gameResult = null;

    public int $score = 0;

    #[Validate( 'required' )]
    #[Validate( 'file' )]
    #[Validate( 'mimetypes:video/*' )]
    #[Validate( 'max:20000', message: 'Scream file must be less than 20MB.' )]
    public ?UploadedFile $video = null;

    public function store( Request $request ): void {
        $this->gameResult = new ScreamGameResult;

        $this->gameResult->createMedia( $this->video );

        if ( empty( $this->gameResult->media ) ) {
            session()->flash( 'error', 'There was an error uploading your scream video. Maybe try again?' );
        }

        if ( $this->gameResult->analyzeMedia() ) {
            session()->flash( 'success', 'Scream result processed.' );

            $this->score = $this->gameResult->calculateScore();
        } else {
            session()->flash( 'error', 'There was an error analyzing your scream video. Maybe try again?' );
        }
    }
} ?>

<div>
    @session('error')
    <mark>Error!</mark>
    <p>{{$value}}</p>
    @endsession

    @session('success')
    <mark>Success!</mark>

    <p>Your score is {{$score}}.</p>
    @else
        <form wire:submit="store">
            <label>
                Upload a video of you screaming
                <input type="file"
                       wire:model="video"
                       name="scream_file"
                       value="{{old('scream_file')}}"
                       required
                       accept="video/*"
                       @error('scream_file') aria-invalid="true"
                       @enderror
                       aria-describedby="scream-file-helper-text"
                />

                @error('scream_file')
                <small id="scream-file-helper-text">{{$message}}</small>
                @enderror
            </label>

            <button type="submit">Rate my scream</button>
        </form>
        @endsession
</div>
