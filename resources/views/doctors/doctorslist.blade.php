
<form action="{{ route('specializationfilter') }}" method="GET">
    <div class="form-group">
        <label for="specialization_name">Enter Specialization Name:</label>
        <input type="text" name="specialization_name" id="specialization_name" class="form-control" value="{{ request('specialization_name') }}">
    </div>
    <button type="submit" class="btn btn-primary">Search</button>
</form>


<form  action="{{ route('searchdoctor') }} " method="GET">
    <div class="form-group">
        <input type="text" name="search" placeholder="Search" value="{{ request('search') }}">
    </div>
    <div class="form-group">
        <button type="submit">Search</button>
    </div>
</form>








@foreach ($doctors as $doctor)
<a href="/doctorpatientdetails/{{$doctor->id}}"><p>Doctor Name: {{ $doctor->user->first_name }}</p></a>
<p>Doctor email: {{ $doctor->user->email }}</p>
<p>Doctor Title: {{ $doctor->user->title }}</p>
<p>Doctor Description: {{ $doctor->user->description }}</p>
<p>Doctor dob: {{ $doctor->user->dob }}</p>
<p>Doctor contact: {{ $doctor->user->contact }}</p>
<p>Specializations: {{ $doctor->specialization_names }}</p>
    <!-- Other doctor information -->
@endforeach 
